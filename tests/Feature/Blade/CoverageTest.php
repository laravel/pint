<?php

it('has a dedicated test file for every fixture group', function (string $group) {
    $studly = str_replace(' ', '', ucwords(str_replace('-', ' ', $group)));

    expect(__DIR__."/{$studly}Test.php")->toBeFile(
        "Fixture group [{$group}] is missing its test file [{$studly}Test.php]. Add it with: bladeFixtureTest('{$group}');",
    );

    expect(bladeFixtureGroupFiles($group))->not->toBeEmpty(
        "Fixture group [{$group}] has no fixtures.",
    );
})->with(fn () => array_map('basename', glob(bladeFixtureRoot().'/*', GLOB_ONLYDIR)));

it('pairs every blade fixture with a golden file', function () {
    $missing = [];

    foreach (bladeFixtureFiles() as $file) {
        if (! is_file(bladeFixtureRoot().'/'.$file.'.expected')) {
            $missing[] = $file;
        }
    }

    expect($missing)->toBe([], 'These fixtures are missing a ".expected" golden file: '.implode(', ', $missing));
});

it('does not leave orphaned golden files behind', function () {
    $orphans = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(bladeFixtureRoot(), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php.expected')) {
            $input = substr($file->getPathname(), 0, -strlen('.expected'));

            if (! is_file($input)) {
                $orphans[] = $file->getPathname();
            }
        }
    }

    expect($orphans)->toBe([], 'These golden files have no matching input: '.implode(', ', $orphans));
});
