<?php

use App\Fixers\LaravelBlade\SkipBladeFilesFixer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;

/**
 * Run the given fixer, decorated, over the given file.
 */
function fixDecorated(FixerInterface $fixer, string $code, string $filename): string
{
    $tokens = Tokens::fromCode($code);

    (new SkipBladeFilesFixer($fixer))->fix(new SplFileInfo($filename), $tokens);

    return $tokens->generateCode();
}

it('registers itself under the name of the fixer it wraps', function () {
    $fixer = new SkipBladeFilesFixer($wrapped = new FullyQualifiedStrictTypesFixer);

    expect($fixer->getName())->toBe('fully_qualified_strict_types')
        ->and($fixer->getPriority())->toBe($wrapped->getPriority())
        ->and($fixer->isRisky())->toBe($wrapped->isRisky())
        ->and($fixer->getDefinition())->toEqual($wrapped->getDefinition())
        ->and($fixer->getConfigurationDefinition())->toEqual($wrapped->getConfigurationDefinition());
});

it('takes on every file but the blade ones', function (string $filename, bool $supported) {
    $fixer = new SkipBladeFilesFixer(new FullyQualifiedStrictTypesFixer);

    expect($fixer->supports(new SplFileInfo($filename)))->toBe($supported);
})->with([
    ['/app/resources/views/welcome.blade.php', false],
    ['/app/resources/views/nested/welcome.blade.php', false],
    ['C:\\app\\resources\\views\\welcome.blade.php', false],
    ['welcome.blade.php', false],
    ['/app/Http/Controllers/Controller.php', true],
    ['/app/resources/views/helpers.php', true],
    ['/app/resources/views/blade.php', true],
]);

it('hands its configuration to the fixer it wraps', function () {
    $in = <<<'PHP'
    <?php

    $user = \App\Models\User::first();

    PHP;

    $fixer = new FullyQualifiedStrictTypesFixer;

    // The option lives on the wrapped fixer, so it has to travel through the decorator.
    (new SkipBladeFilesFixer($fixer))->configure(['import_symbols' => true]);

    expect(fixDecorated($fixer, $in, 'Controller.php'))->toContain('use App\Models\User;');
});

it('hands the whitespaces configuration to the fixer it wraps', function () {
    $fixer = new DeclareStrictTypesFixer;

    (new SkipBladeFilesFixer($fixer))->setWhitespacesConfig($config = new WhitespacesFixerConfig('  ', "\r\n"));

    $property = (new ReflectionClass(AbstractFixer::class))->getProperty('whitespacesConfig');

    expect($property->getValue($fixer))->toBe($config);
});

it('leaves the raw php islands of a blade file alone', function () {
    $in = <<<'BLADE'
    <div>
    <?php
    $rows = \App\Models\Category::all();
    ?>
    </div>

    BLADE;

    $fixer = new FullyQualifiedStrictTypesFixer;

    $fixer->configure(['import_symbols' => true]);

    expect(fixDecorated($fixer, $in, 'view.blade.php'))->toBe($in)
        // The same file as plain php: the import lands in the middle of the markup.
        ->and(fixDecorated($fixer, $in, 'helpers.php'))->toContain('use App\Models\Category;');
});
