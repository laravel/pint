<?php

use App\Repositories\ConfigurationJsonRepository;

it('works without json file', function () {
    $repository = new ConfigurationJsonRepository(null, 'psr12');

    expect($repository->finder())->toBeEmpty()
        ->and($repository->rules())->toBeEmpty();
});

it('works with a remote json file', function () {
    $repository = new ConfigurationJsonRepository('https://raw.githubusercontent.com/laravel/pint/main/tests/Fixtures/rules/pint.json', 'psr12');

    expect($repository->rules())->toBe([
        'no_unused_imports' => false,
    ]);
});

it('may have rules options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules/pint.json', 'psr12');

    expect($repository->rules())->toBe([
        'no_unused_imports' => false,
    ]);
});

it('may have finder options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/finder/pint.json', null);

    expect($repository->finder())->toBe([
        'exclude' => [
            'my-dir',
        ],
        'notName' => [
            '*-my-file.php',
        ],
        'notPath' => [
            'path/to/excluded-file.php',
        ],
    ]);
});

it('may have an indent option', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/indent/pint.json', null);

    expect($repository->indent())->toBe("\t");
});

it('may have a line ending option', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/line-ending/pint.json', null);

    expect($repository->lineEnding())->toBe("\r\n");
});

it('may have a preset option', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/preset/pint.json', null);

    expect($repository->preset())->toBe('laravel');
});
