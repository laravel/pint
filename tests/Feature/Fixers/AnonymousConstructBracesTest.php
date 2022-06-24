<?php

it('fixes the code', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-anonymous-constructs'),
        '--preset' => 'laravel',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('  .')
        ->toContain('  PASS')
        ;
});
