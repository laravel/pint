<?php

use App\Repositories\ConfigurationJsonRepository;
use LaravelZero\Framework\Exceptions\ConsoleException;

it('ensures configuration file is valid', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-invalid-configuration'),
    ]);
})->throws(ConsoleException::class, 'is not valid JSON.');

it('extends existing configuration', function () {
    chdir(base_path('tests/Fixtures/with-extended-configuration'));

    expect(new ConfigurationJsonRepository(base_path('tests/Fixtures/with-extended-configuration/pint.json'), null))
    ->finder()->toBe([
        'notName' => ['*-my-file.php'],
        'exclude' => ['my-specific/folder'],
    ])
    ->rules()->toBe([
        'declare_strict_types' => false,
        'octal_notation' => true,
        'no_unused_imports' => true,
    ])
    ->preset()->toBe('laravel');
});
