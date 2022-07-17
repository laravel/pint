<?php

it('saves report file', function () {
    $report = getcwd() . '/checkstyle.xml';
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
        '--format' => 'checkstyle',
        '--report' => $report,
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain(sprintf('⨯ %s', implode(DIRECTORY_SEPARATOR, [
            'tests', 'Fixtures', 'with-fixable-issues', 'file.php',
        ])))
        ->and(file_exists($report))
        ->toBeTrue()
        ->and(file_get_contents($report))
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<checkstyle>')
        ->toContain('</checkstyle>')
        ->and(unlink($report))
        ->toBeTrue();
});
