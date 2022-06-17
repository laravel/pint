<?php

use App\Repositories\ConfigurationJsonRepository;

it('works without json file', function () {
    $repository = new ConfigurationJsonRepository(__DIR__, 'psr12');

    expect($repository->finder())->toBeEmpty()
        ->and($repository->rules())->toBeEmpty();
});

it('may have rules options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules', 'psr12');

    expect($repository->rules())->toBe([
        'no_unused_imports' => false,
    ]);
});

it('may have finder options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/finder', null);

    expect($repository->finder())->toBe([
        'exclude' => [
            'my-dir',
        ],
    ]);
});

it('may have a preset option', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/preset', null);

    expect($repository->preset())->toBe('laravel');
});
