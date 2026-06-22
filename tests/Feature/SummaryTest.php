<?php

it('may fail with style issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('FAIL')
        ->toContain('1 file, 1 style issue')
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))->toContain('new_with_parentheses');
});

it('may fail with errors', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-non-fixable-issues'),
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('FAIL')
        ->toContain('1 file, 1 error')
        ->toContain(sprintf('! %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-non-fixable-issues', 'file.php',
        ])))->toContain('Parse error: syntax error');
});

it('may pass', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('PASS');
});

it('writes style issues to stderr and nothing to stdout when --quiet is set', function () {
    [$statusCode, $stdout, $stderr] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--quiet' => true,
    ]);

    expect($statusCode)->toBe(1)
        ->and($stdout)->toBe('')
        ->and($stderr)->toContain(implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ]));
});

it('writes nothing to stderr when --quiet is set and there are no issues', function () {
    [$statusCode, $stdout, $stderr] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
        '--quiet' => true,
    ]);

    expect($statusCode)->toBe(0)
        ->and($stdout)->toBe('')
        ->and($stderr)->toBe('');
});
