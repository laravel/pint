<?php

use Illuminate\Contracts\Console\Kernel;

it('detects issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-issues'),
    ]);

    expect($statusCode)->toBe(8)
        ->and($output->fetch())
        ->toContain('0 files are respecting the PSR 12 coding style. However, 1 file have issues.');
});

it('may not detect issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-issues'),
    ]);

    expect($statusCode)->toBe(8)
        ->and($output->fetch())
        ->toContain('0 files are respecting the PSR 12 coding style. However, 1 file have issues.');
});
