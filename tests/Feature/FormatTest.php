<?php

it('outputs checkstyle format', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'checkstyle',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<checkstyle')
        ->toContain('</checkstyle>')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs json format', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'json',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toBeJson()
        ->toContain('appliedFixers')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs xml format', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'xml',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs junit format', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'junit',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('CDATA')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs gitlab format', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'gitlab',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toBeJson()
        ->toContain('fingerprint')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});
