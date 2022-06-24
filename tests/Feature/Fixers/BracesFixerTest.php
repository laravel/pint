<?php

it('fixes the code', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/fixers/braces.php'),
        '--preset' => 'laravel',
    ]);

    expect($statusCode)->toBe(1)
        ->and($output)
        ->toContain('  ⨯')
        ->toContain(
            <<<'EOF'
  -$a = function ()
  -{
  +$a = function () {
       // ..
   };
EOF,
        )->toContain(
            <<<'EOF'
  -new class {
  +new class
  +{
       // ..
   };
EOF,
        )->toContain(
            <<<'EOF'
  -new class extends stdClass {
  +new class extends stdClass
  +{
       // ..
   };
EOF,
        );
});
