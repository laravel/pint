<?php

use Symfony\Component\Process\Process;

/**
 * Every prettier fixture file across all groups, as paths relative to the
 * fixture root.
 *
 * @return array<int, string>
 */
function prettierFixtureFiles(): array
{
    $root = dirname(__DIR__, 2).'/Fixtures/prettier-formatting';

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    $files = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && ! str_ends_with($file->getFilename(), '.expected')) {
            $files[] = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
        }
    }

    sort($files);

    return $files;
}

/**
 * Stage every fixture twice ("input/" and "golden/") into a temporary project
 * with both prettier rules enabled, then run Pint over it once. The single run
 * covers the golden-file check (does "input" format to "golden"?) and the
 * idempotency check (does "golden" survive a re-format?).
 */
function formatPrettierFixtureProject(): string
{
    static $tmp = null;

    if ($tmp !== null) {
        return $tmp;
    }

    $sourceRoot = dirname(__DIR__, 2).'/Fixtures/prettier-formatting';

    $tmp = freshBladeTempDirectory();

    foreach (prettierFixtureFiles() as $relative) {
        stageBladeFixture($tmp, $sourceRoot, $relative, targetPrefix: 'input/');
        stageBladeFixture($tmp, $sourceRoot, $relative, fromExpected: true, targetPrefix: 'golden/');
    }

    file_put_contents($tmp.'/pint.json', json_encode([
        'preset' => 'laravel',
        'rules' => [
            'Pint/prettier_js' => true,
            'Pint/prettier_css' => true,
        ],
    ]).PHP_EOL);

    $process = new Process(['php', 'pint', '--config', $tmp.'/pint.json', $tmp], base_path());

    $process->setTimeout(120);

    $process->run();

    expect($process->getExitCode())->toBe(
        0,
        'pint failed while formatting the prettier fixtures: '.$process->getErrorOutput().$process->getOutput(),
    );

    return $tmp;
}

it('formats every fixture to its golden file', function (string $file) {
    $tmp = formatPrettierFixtureProject();

    expect(file_get_contents($tmp.'/input/'.$file))->toBe(
        file_get_contents(dirname(__DIR__, 2).'/Fixtures/prettier-formatting/'.$file.'.expected'),
        "Formatted output does not match the golden file for [{$file}].",
    );
})->with(fn () => prettierFixtureFiles());

it('re-formats every golden file unchanged (idempotent)', function (string $file) {
    $tmp = formatPrettierFixtureProject();

    expect(file_get_contents($tmp.'/golden/'.$file))->toBe(
        file_get_contents(dirname(__DIR__, 2).'/Fixtures/prettier-formatting/'.$file.'.expected'),
        "Re-formatting the golden file changed it for [{$file}] (not idempotent).",
    );
})->with(fn () => prettierFixtureFiles());
