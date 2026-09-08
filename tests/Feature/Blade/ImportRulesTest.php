<?php

use Symfony\Component\Process\Process;

/**
 * Format a project of the given "path => contents" files with the given rules,
 * and return the formatted contents, keyed the same way.
 *
 * @param  array<string, string>  $files
 * @param  array<string, mixed>  $rules
 * @return array<string, string>
 */
function formatWithRules(array $files, array $rules): array
{
    $tmp = freshBladeTempDirectory();

    foreach ($files as $path => $contents) {
        @mkdir(dirname($tmp.'/'.$path), 0777, true);
        file_put_contents($tmp.'/'.$path, $contents);
    }

    file_put_contents($tmp.'/pint.json', json_encode(['preset' => 'laravel', 'rules' => $rules]));

    $process = new Process(['php', 'pint', '--blade', '--config', $tmp.'/pint.json', $tmp], base_path());

    $process->setTimeout(120);
    $process->run();

    expect($process->getExitCode())->toBe(
        0,
        'pint --blade failed: '.$process->getErrorOutput().$process->getOutput(),
    );

    return array_combine(
        array_keys($files),
        array_map(fn (string $path): string => file_get_contents($tmp.'/'.$path), array_keys($files)),
    );
}

it('does not declare strict types on a blade template', function () {
    $blade = <<<'BLADE'
    <div>
        <?php $rows = collect([]); ?>
    </div>

    BLADE;

    $php = <<<'PHP'
    <?php

    $rows = collect([]);

    PHP;

    $formatted = formatWithRules(
        ['view.blade.php' => $blade, 'helpers.php' => $php],
        ['declare_strict_types' => true],
    );

    expect($formatted['view.blade.php'])->not->toContain('declare(strict_types=1)')
        // The same rule on a plain ".php" file still applies as usual.
        ->and($formatted['helpers.php'])->toContain('declare(strict_types=1)');
});

it('does not import the global namespace into a blade template', function () {
    $blade = <<<'BLADE'
    <div>
        <?php
        namespace App\Views;

        $date = new \DateTimeImmutable('now');
        ?>
    </div>

    BLADE;

    $php = <<<'PHP'
    <?php

    namespace App\Views;

    $date = new \DateTimeImmutable('now');

    PHP;

    $formatted = formatWithRules(
        ['view.blade.php' => $blade, 'helpers.php' => $php],
        ['global_namespace_import' => ['import_classes' => true]],
    );

    expect($formatted['view.blade.php'])->not->toContain('use DateTimeImmutable;')
        ->and($formatted['helpers.php'])->toContain('use DateTimeImmutable;');
});

it('does not import symbols into a blade template, whatever the fixer is configured to do', function () {
    $blade = <<<'BLADE'
    <div>
        <?php $rows = \App\Models\Category::all(); ?>
    </div>

    BLADE;

    $php = <<<'PHP'
    <?php

    $rows = \App\Models\Category::all();

    PHP;

    $formatted = formatWithRules(
        ['view.blade.php' => $blade, 'helpers.php' => $php],
        ['fully_qualified_strict_types' => ['import_symbols' => true, 'leading_backslash_in_global_namespace' => true]],
    );

    expect($formatted['view.blade.php'])->toContain('\App\Models\Category::all()')
        ->not->toContain('use App\Models\Category;')
        ->and($formatted['helpers.php'])->toContain('use App\Models\Category;');
});

it('still drops the unused imports of a plain php file that lives among the views', function () {
    $blade = <<<'BLADE'
    <?php

    use App\Enums\CalculationMode;
    ?>

    <div class="grid grid-cols-{{ CalculationMode::count() }}"></div>

    BLADE;

    $php = <<<'PHP'
    <?php

    use App\Enums\CalculationMode;

    $mode = 'all';

    PHP;

    $formatted = formatWithRules(
        ['resources/views/card.blade.php' => $blade, 'resources/views/helpers.php' => $php],
        ['no_unused_imports' => true],
    );

    expect($formatted['resources/views/card.blade.php'])->toContain('use App\Enums\CalculationMode;')
        ->and($formatted['resources/views/helpers.php'])->not->toContain('use App\Enums\CalculationMode;');
});
