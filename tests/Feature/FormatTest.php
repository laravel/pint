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

it('outputs agent format with fail status on test mode', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'agent',
    ]);

    $json = json_decode($output, true);

    expect($statusCode)->toBe(1)
        ->and($output)->toBeJson()
        ->and($json['status'])->toBe('fail')
        ->and($json)->toHaveKey('files')
        ->and($json['files'][0])->toHaveKeys(['path', 'fixers'])
        ->and($json['files'][0]['fixers'])->toBeArray()
        ->and($json)->not->toHaveKey('about')
        ->and($json)->not->toHaveKey('time')
        ->and($json)->not->toHaveKey('memory');
});

it('outputs agent format with pass status when no issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
        '--format' => 'agent',
    ]);

    $json = json_decode($output, true);

    expect($statusCode)->toBe(0)
        ->and($output)->toBeJson()
        ->and($json['status'])->toBe('pass')
        ->and($json)->not->toHaveKey('files');
});

it('auto-detects agent format via OPENCODE env var', function () {
    putenv('OPENCODE=1');

    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
    ]);

    putenv('OPENCODE');

    $json = json_decode($output, true);

    expect($statusCode)->toBe(1)
        ->and($output)->toBeJson()
        ->and($json)->toHaveKey('files')
        ->and($json['files'][0])->toHaveKeys(['path', 'fixers']);
});

it('auto-detects agent format via CLAUDECODE env var', function () {
    putenv('CLAUDECODE=1');

    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
    ]);

    putenv('CLAUDECODE');

    $json = json_decode($output, true);

    expect($statusCode)->toBe(1)
        ->and($output)->toBeJson()
        ->and($json)->toHaveKey('files')
        ->and($json['files'][0])->toHaveKeys(['path', 'fixers']);
});
