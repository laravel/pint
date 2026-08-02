<?php

use App\Fixers\LaravelBlade\NoUnusedImportsFixer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Run the blade-aware "no_unused_imports" fixer over the given file.
 */
function fixImports(string $code, string $filename = 'view.blade.php'): string
{
    $fixer = new NoUnusedImportsFixer(new PhpCsFixer\Fixer\Import\NoUnusedImportsFixer);

    $tokens = Tokens::fromCode($code);

    $fixer->fix(new SplFileInfo($filename), $tokens);

    return $tokens->generateCode();
}

it('registers itself under the name of the fixer it wraps', function () {
    $fixer = new NoUnusedImportsFixer($wrapped = new PhpCsFixer\Fixer\Import\NoUnusedImportsFixer);

    expect($fixer->getName())->toBe('no_unused_imports')
        ->and($fixer->getPriority())->toBe($wrapped->getPriority());
});

it('hands the definition and the candidacy check to the fixer it wraps', function () {
    $fixer = new NoUnusedImportsFixer($wrapped = new PhpCsFixer\Fixer\Import\NoUnusedImportsFixer);

    expect($fixer->getDefinition())->toEqual($wrapped->getDefinition())
        ->and($fixer->isRisky())->toBe($wrapped->isRisky())
        ->and($fixer->isCandidate(Tokens::fromCode("<?php\n\n\$default = 'all';\n")))->toBeFalse()
        ->and($fixer->isCandidate(Tokens::fromCode("<?php\n\nuse App\Models\User;\n")))->toBeTrue();
});

it('takes on every file but the blade ones', function (string $filename, bool $supported) {
    $fixer = new NoUnusedImportsFixer(new PhpCsFixer\Fixer\Import\NoUnusedImportsFixer);

    expect($fixer->supports(new SplFileInfo($filename)))->toBe($supported);
})->with([
    ['/app/resources/views/welcome.blade.php', false],
    ['/app/resources/views/nested/welcome.blade.php', false],
    ['C:\\app\\resources\\views\\welcome.blade.php', false],
    ['welcome.blade.php', false],
    ['/app/Http/Controllers/Controller.php', true],
    // A ".php" file is still a ".php" file, wherever among the views it lives.
    ['/app/resources/views/helpers.php', true],
    ['/app/resources/views/blade.php', true],
]);

it('keeps an import the markup is the only user of', function () {
    $in = <<<'BLADE'
    <?php

    use App\Enums\CalculationMode;

    $default = 'all';
    ?>

    <div>{{ CalculationMode::count() }}</div>

    BLADE;

    expect(fixImports($in))->toBe($in);
});

it('keeps an import nothing at all uses, which is the author\'s to remove', function () {
    $in = <<<'BLADE'
    <?php

    use App\Models\User;

    $default = 'all';
    ?>

    <div>{{ $default }}</div>

    BLADE;

    expect(fixImports($in))->toBe($in);
});

it('keeps an aliased import, a function import and a constant import the markup is the only user of', function () {
    $in = <<<'BLADE'
    <?php

    use function App\Support\money;

    use const App\Support\CURRENCY;

    use App\Enums\CalculationMode as Mode;
    ?>

    <div>{{ money(Mode::count(), CURRENCY) }}</div>

    BLADE;

    expect(fixImports($in))->toBe($in)
        // The same file as plain php: every import reads as dead, which is the whole problem.
        ->and(fixImports($in, 'Controller.php'))
        ->not->toContain('use function App\Support\money;')
        ->not->toContain('use const App\Support\CURRENCY;')
        ->not->toContain('use App\Enums\CalculationMode as Mode;');
});

it('keeps an import only a directive uses', function () {
    $in = <<<'BLADE'
    <?php

    use App\Enums\CalculationMode;
    ?>

    @foreach (CalculationMode::cases() as $case)
        <span>{{ $case->label() }}</span>
    @endforeach

    BLADE;

    expect(fixImports($in))->toBe($in);
});

it('keeps an import only a @php block uses', function () {
    $in = <<<'BLADE'
    <?php

    use App\Enums\CalculationMode;
    ?>

    @php($mode = CalculationMode::default())

    <div>{{ $mode }}</div>

    BLADE;

    expect(fixImports($in))->toBe($in);
});

it('keeps the imports of a class-based single file component the markup uses', function () {
    $in = <<<'BLADE'
    <?php

    use App\Enums\CalculationMode;
    use Illuminate\View\Component;

    new class extends Component
    {
        public string $mode = 'all';
    };
    ?>

    <div class="grid grid-cols-{{ CalculationMode::count() }}"></div>

    BLADE;

    expect(fixImports($in))->toBe($in);
});

it('keeps the enum a livewire component only its own markup reaches for', function () {
    // The report this fixer came from: an enum used by an attribute and a directive,
    // and by nothing the fixer can see inside the php block itself.
    $in = <<<'BLADE'
    <?php

    declare(strict_types=1);

    use Livewire\Component;
    use Modules\ProfitabilityCalculator\Enums\ViewMode;

    new class extends Component
    {
        public string $viewMode;
    }; ?>

    <flux:radio.group class="mt-4 grid grid-cols-{{ ViewMode::count() }}" wire:model.live="viewMode">
        @foreach (ViewMode::cases() as $mode)
            <flux:radio value="{{ $mode->value }}">{{ $mode->label() }}</flux:radio>
        @endforeach
    </flux:radio.group>

    BLADE;

    expect(fixImports($in))->toBe($in);
});

it('drops an unused import of a plain php file as usual', function () {
    $in = <<<'PHP'
    <?php

    use App\Models\User;

    $default = 'all';

    PHP;

    expect(fixImports($in, 'Controller.php'))->not->toContain('use App\Models\User;');
});

it('keeps the imports a plain php file does use', function () {
    $in = <<<'PHP'
    <?php

    use App\Models\User;

    $user = User::first();

    PHP;

    expect(fixImports($in, 'Controller.php'))->toBe($in);
});

it('drops an unused import of a php file that lives among the views', function () {
    $in = <<<'PHP'
    <?php

    use App\Models\User;

    $default = 'all';

    PHP;

    expect(fixImports($in, 'resources/views/helpers.php'))->not->toContain('use App\Models\User;');
});
