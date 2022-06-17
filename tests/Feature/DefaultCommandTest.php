<?php

it('detects issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-issues'),
    ]);

    expect($statusCode)->toBe(1)
        ->and($output->fetch())
        ->toContain('FAIL')
        ->toContain('1 file, 1 style issue');
});

it('may not detect issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output->fetch())
        ->toContain('PASS');
});
