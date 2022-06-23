<?php

it('runs custom fixers', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/fixers'),
    ]);

    expect($statusCode)->toBe(1)
        ->and($output->fetch())
        ->toContain('FAIL')
        ->toContain('1 file, 1 style issue')
        ->toContain('⨯ tests/Fixtures/fixers/empty.php')
        ->toContain('Laravel/test_custom_fixer');
});
