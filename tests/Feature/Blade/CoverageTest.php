<?php

/*
|--------------------------------------------------------------------------
| Coverage Guard
|--------------------------------------------------------------------------
|
| These tests keep the fixture/test layout honest as new cases are added: every
| concern directory must have a matching test file, hold at least one fixture,
| and pair every ".blade.php" input with a golden ".expected" file. A new
| fixture dropped into a concern folder is picked up automatically; a brand new
| concern folder fails here until its one-line test file exists.
|
*/

it('has a dedicated test file for every concern', function (string $concern) {
    $studly = str_replace(' ', '', ucwords(str_replace('-', ' ', $concern)));

    expect(__DIR__."/{$studly}Test.php")->toBeFile(
        "Concern [{$concern}] is missing its test file [{$studly}Test.php]. Add it with: bladeFixtureTest('{$concern}');",
    );

    expect(bladeConcernFiles($concern))->not->toBeEmpty(
        "Concern [{$concern}] has no fixtures.",
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
