<?php

$file = \Pest\testDirectory('test-output');

it('outputs checkstyle format to file and pretty print in cli', function () use ($file) {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--output-format' => 'checkstyle',
        '--output-to-file' => $file,
    ]);

    expect($statusCode)->toBe(1)
        ->and(file_get_contents($file))
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<checkstyle')
        ->toContain('</checkstyle>')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and($output)
        ->not->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->not->toContain('<checkstyle')
        ->not->toContain('</checkstyle>')
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs json format to file and pretty print in cli', function () use ($file) {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--output-format' => 'json',
        '--output-to-file' => $file,
    ]);

    expect($statusCode)->toBe(1)
        ->and(file_get_contents($file))
        ->toBeJson()
        ->toContain('appliedFixers')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and($output)
        ->not->toBeJson()
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs xml format to file and pretty print in cli', function () use ($file) {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--output-format' => 'xml',
        '--output-to-file' => $file,
    ]);

    expect($statusCode)->toBe(1)
        ->and(file_get_contents($file))
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and($output)
        ->not->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs junit format to file and pretty print in cli', function () use ($file) {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--output-format' => 'junit',
        '--output-to-file' => $file,
    ]);

    expect($statusCode)->toBe(1)
        ->and(file_get_contents($file))
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('CDATA')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and($output)
        ->not->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->not->toContain('CDATA')
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs gitlab format to file and pretty print in cli', function () use ($file) {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--output-format' => 'gitlab',
        '--output-to-file' => $file,
    ]);

    expect($statusCode)->toBe(1)
        ->and(file_get_contents($file))
        ->toBeJson()
        ->toContain('fingerprint')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and($output)
        ->not->toBeJson()
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));
});

it('outputs json format file and xml format in cli', function () use ($file) {

    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'xml',
        '--output-format' => 'json',
        '--output-to-file' => $file,
    ]);

    expect($statusCode)->toBe(1)
        ->and(file_get_contents($file))
        ->toBeJson()
        ->toContain('appliedFixers')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and($output)
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->not->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])));

});
