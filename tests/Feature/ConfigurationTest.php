<?php

use LaravelZero\Framework\Exceptions\ConsoleException;

$exceptionMessage = sprintf(
    'The configuration file [%s/tests/Fixtures/with-invalid-configuration/pint.json] is not valid JSON format.',
    getcwd(),
);

it('ensures configuration file is valid', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-invalid-configuration'),
    ]);
})->throws(ConsoleException::class, $exceptionMessage);
