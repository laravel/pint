<?php

beforeEach(function () {
    $this->contents = file_get_contents(base_path('tests/Fixtures/with-fixable-issues/file.php'));
});

afterEach(function () {
    file_put_contents(base_path('tests/Fixtures/with-fixable-issues/file.php'), $this->contents);
});

it('exits with status 1 with fixes', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--repair' => true,
        '--test' => false,
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('FIXED');
});

it('exits with status 0 without fixes', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
        '--repair' => true,
        '--test' => false,
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('PASS');
});
