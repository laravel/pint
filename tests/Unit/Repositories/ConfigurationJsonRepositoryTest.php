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

it('may have a preset option', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/preset/pint.json', null);

    expect($repository->preset())->toBe('laravel');
});

it('properly extend the base config file', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/extend/pint.json', null);

    expect($repository->preset())->toBe('laravel')
        ->and($repository->rules())->toBe([
            'array_push' => true,
            'backtick_to_shell_exec' => true,
            'date_time_immutable' => true,
            'final_internal_class' => true,
            'final_public_method_for_abstract_class' => true,
            'fully_qualified_strict_types' => false,
            'global_namespace_import' => [
                'import_classes' => true,
                'import_constants' => true,
                'import_functions' => true,
            ],
            'declare_strict_types' => true,
            'lowercase_keywords' => true,
            'lowercase_static_reference' => true,
            'final_class' => true,
        ]);
});

it('throw an error if the extended configuration also has an extend', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/extend_recursive/pint.json', null);

    $repository->finder();
})->throws(LogicException::class);

it('normalizes cast_spaces false to none', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules/pint_cast_spaces_false.json', null);

    expect($repository->rules())->toBe([
        'cast_spaces' => ['space' => 'none'],
    ]);
});

it('normalizes cast_spaces true to single', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules/pint_cast_spaces_true.json', null);

    expect($repository->rules())->toBe([
        'cast_spaces' => ['space' => 'single'],
    ]);
});

it('preserves explicit cast_spaces array', function () {
    $repository = new ConfigurationJsonRepository(dirname(__DIR__, 2).'/Fixtures/rules/pint_cast_spaces_array.json', null);

    expect($repository->rules())->toBe([
        'cast_spaces' => ['space' => 'single'],
    ]);
});
