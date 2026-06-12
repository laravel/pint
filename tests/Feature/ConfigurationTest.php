<?php

use LaravelZero\Framework\Exceptions\ConsoleException;

it('ensures configuration file is valid', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-invalid-configuration'),
    ]);
})->throws(ConsoleException::class, 'is not valid JSON.');

it('rejects a configuration loaded over plaintext http', function () {
    run('default', [
        '--config' => 'http://example.com/pint.json',
    ]);
})->throws(ConsoleException::class, 'Loading the configuration over plaintext HTTP is not allowed. Use HTTPS.');
