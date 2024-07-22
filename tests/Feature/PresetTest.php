<?php

it('uses the laravel preset by default', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel');
});

it('may use the PSR 12 preset', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
        '--preset' => 'psr12',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── PSR 12');
});

it('may use the PER preset', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
        '--preset' => 'per',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── PER');
});

it('may use the Laravel preset', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
        '--preset' => 'laravel',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel');
});

it('may use the Symfony preset', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
        '--preset' => 'symfony',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Symfony');
});

it('ignores config when using no config option', function () {
    $cwd = getcwd();
    chdir(base_path('tests/Fixtures/no-config'));

    [$statusCode, $output] = run('default', [
        '--preset' => 'psr12',
        '--no-config' => true,
    ]);

    chdir($cwd);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── PSR 12');
});
