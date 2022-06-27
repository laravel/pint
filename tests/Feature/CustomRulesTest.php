<?php

it('uses a specified custom rule', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/custom-rules'),
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('── PSR 12')
        ->toContain('  ⨯')
        ->toContain("+//\n");
});
