<?php

it('uses the laravel preset by default', function () {
    [$statusCode, $output] = run('default', [
        'path' => base_path('tests/Fixtures/without-issues-laravel'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel');
});

it('may use preset', function (string $presetName, string $presetTitle, string $fixture) {
    [$statusCode, $output] = run('default', [
        'path' => base_path("tests/Fixtures/{$fixture}"),
        '--preset' => $presetName,
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain($presetTitle);
})->with('pint_presets');

it('ignores config when using no config option', function (string $presetName, string $presetTitle) {
    $cwd = getcwd();
    chdir(base_path('tests/Fixtures/no-config'));

    [$statusCode, $output] = run('default', [
        '--preset' => $presetName,
        '--no-config' => true,
    ]);

    chdir($cwd);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain($presetTitle);
})->with('pint_presets');

dataset('pint_presets', [
    "Laravel" => ['laravel', '── Laravel', 'without-issues-laravel'],
    "PER" => ['per', '── PER', 'without-issues'],
    "PSR2" => ['psr2', '── PSR 2', 'without-issues'],
    "PSR12" => ['psr12', '── PSR 12', 'without-issues'],
    "Symfony" => ['symfony', '── Symfony', 'without-issues'],
]);
