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

it('uses configured in paths when no path is provided', function () {
    $cwd = getcwd();

    chdir(base_path('tests/Fixtures/finder-in'));

    try {
        [$statusCode, $output] = run('default', []);
    } finally {
        chdir($cwd);
    }

    $output = str_replace('\\/', '/', $output);

    expect($statusCode)->toBe(1)
        ->and($output)->toContain('included/file.php')
        ->and($output)->not->toContain('excluded/file.php');
});

it('uses explicit paths over configured in paths', function () {
    $fixture = base_path('tests/Fixtures/finder-in');
    $cwd = getcwd();

    chdir($fixture);

    try {
        [$statusCode, $output] = run('default', [
            'path' => $fixture.'/excluded',
            '--config' => $fixture.'/pint.json',
        ]);
    } finally {
        chdir($cwd);
    }

    $output = str_replace('\\/', '/', $output);

    expect($statusCode)->toBe(1)
        ->and($output)->toContain('excluded/file.php')
        ->and($output)->not->toContain('included/file.php');
});
