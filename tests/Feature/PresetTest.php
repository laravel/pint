<?php

it('uses the laravel preset by default', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
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

it('may use the Laravel preset', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
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

it('may use custom preset', function () {
    chdir(base_path('tests/Fixtures/custom-preset'));

    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/custom-preset'),
        '--preset' => 'preset.json',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── preset.json');
});
