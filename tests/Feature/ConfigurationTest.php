<?php

use LaravelZero\Framework\Exceptions\ConsoleException;

it('ensures configuration file is valid', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-invalid-configuration'),
        '--config' => base_path('tests/Fixtures/with-invalid-configuration/pint.json'),
    ]);
})->throws(ConsoleException::class, 'is not valid JSON.');
