<?php

use Illuminate\Contracts\Console\Kernel;

it('detects issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-issues'),
    ]);

    expect($statusCode)->toBe(8)
        ->and($output->fetch())
        ->toContain('FAIL')
        ->toContain('1 files, 1 file(s) failed');
});

it('may not detect issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output->fetch())
        ->toContain('PASS');
});
