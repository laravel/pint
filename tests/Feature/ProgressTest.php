<?php

it('display progress when fixing issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('  ⨯');
});

it('display progress when detecting non fixable issues', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-non-fixable-issues'),
        '--preset' => 'psr12',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('  !');
});

it('display progress when no issues were found', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues'),
        '--preset' => 'psr12',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('  .');
});
