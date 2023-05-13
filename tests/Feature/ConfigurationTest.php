<?php

use LaravelZero\Framework\Exceptions\ConsoleException;

it('ensures configuration file is valid', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-invalid-configuration'),
    ]);
})->throws(ConsoleException::class, 'is not valid configuration.');

it('ensures yaml configuration file is valid', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-invalid-yaml-configuration'),
    ]);
    dump($statusCode, $output);
})->throws(ConsoleException::class, 'is not valid YAML.');
