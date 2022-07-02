<?php

it('fixes the code', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/fixers/binary_operator_spaces.php'),
        '--preset' => 'laravel',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain(
            <<<'EOF'
  -    'long_item_name' =>  'value',
  -    'short'          =>  'value',
  +    'long_item_name' => 'value',
  +    'short' => 'value',
EOF,
        )->toContain(
            <<<'EOF'
  -$array = array_filter($array, fn ($item)  =>  $item === 'value');
  +$array = array_filter($array, fn ($item) => $item === 'value');
EOF,
        );
});
