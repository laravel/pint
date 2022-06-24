<?php

it('displays the code diff', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/with-fixable-issues'),
        '--preset' => 'psr12',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('-$a = new stdClass;')
        ->toContain('+$a = new stdClass()');
});
