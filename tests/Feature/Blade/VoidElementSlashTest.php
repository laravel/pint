<?php

use Symfony\Component\Process\Process;

/**
 * Format a single blade view with the given "blade" options in "pint.json",
 * and return the formatted contents.
 *
 * @param  array<string, bool>  $blade
 */
function formatWithBladeOptions(string $view, array $blade): string
{
    $tmp = freshBladeTempDirectory();

    file_put_contents($tmp.'/view.blade.php', $view);
    file_put_contents($tmp.'/pint.json', json_encode([
        'preset' => 'laravel',
        'blade' => $blade,
    ]));

    $process = new Process(['php', 'pint', '--blade', '--config', $tmp.'/pint.json', $tmp], base_path());

    $process->setTimeout(120);
    $process->run();

    expect($process->getExitCode())->toBe(
        0,
        'pint --blade failed: '.$process->getErrorOutput().$process->getOutput(),
    );

    return file_get_contents($tmp.'/view.blade.php');
}

$view = <<<'BLADE'
<div>
<meta charset="utf-8">
<br>
<input type="text" name="email">
<img src="/logo.png" alt="Logo">
<x-icon name="star" />
<livewire:counter />
<svg viewBox="0 0 16 16"><path d="M0 0" /></svg>
</div>

BLADE;

it('keeps prettier\'s terminator on void elements by default', function () use ($view) {
    expect(formatWithBladeOptions($view, []))
        ->toContain('<meta charset="utf-8" />')
        ->toContain('<br />')
        ->toContain('<input type="text" name="email" />')
        ->toContain('<img src="/logo.png" alt="Logo" />');
});

it('drops the terminator on void elements when the option is disabled', function () use ($view) {
    expect(formatWithBladeOptions($view, ['void_element_slash' => false]))
        ->toContain('<meta charset="utf-8">')
        ->toContain('<br>')
        ->toContain('<input type="text" name="email">')
        ->toContain('<img src="/logo.png" alt="Logo">');
});

it('never drops the terminator outside of void elements', function () use ($view) {
    expect(formatWithBladeOptions($view, ['void_element_slash' => false]))
        ->toContain('<x-icon name="star" />')
        ->toContain('<livewire:counter />')
        ->toContain('<path d="M0 0" />');
});
