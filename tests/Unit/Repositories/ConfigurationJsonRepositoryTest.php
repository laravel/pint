<?php

use App\Repositories\ConfigurationJsonRepository;
use App\Repositories\ConfigurationLoaderResolver;

it('works without json file', function () {
    $repository = new ConfigurationJsonRepository(null, 'psr12', new ConfigurationLoaderResolver());

    expect($repository->finder())->toBeEmpty()
        ->and($repository->rules())->toBeEmpty();
});

it('may have rules options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules/pint.json', 'psr12', new ConfigurationLoaderResolver());

    expect($repository->rules())->toBe([
        'no_unused_imports' => false,
    ]);
});

it('may have finder options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/finder/pint.json', null, new ConfigurationLoaderResolver());

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

it('may have a preset option', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/preset/pint.json', null, new ConfigurationLoaderResolver());

    expect($repository->preset())->toBe('laravel');
});
