<?php

it('runs custom fixers', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/files/empty.php'),
        '--config' => base_path('tests/Fixtures/fixers/pint.json'),
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('FAIL')
        ->toContain('1 file, 1 style issue')
        ->toContain('⨯ tests/Fixtures/files/empty.php')
        ->toContain('Custom/fixer');
});
