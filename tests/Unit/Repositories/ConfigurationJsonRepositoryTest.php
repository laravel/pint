<?php

use App\Repositories\ConfigurationJsonRepository;

it('works without json file', function () {
    $repository = new ConfigurationJsonRepository(__DIR__);

    expect($repository->finder())->toBeEmpty();
    expect($repository->rules())->toBeEmpty();
});

it('may have rules options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules');

    expect($repository->rules())->toBe([
        'no_unused_imports' => false,
    ]);
});

it('may have finder options', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/finder');

    expect($repository->finder())->toBe([
        'exclude' => [
            'my-dir',
        ],
    ]);
});
