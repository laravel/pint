<?php

use Symfony\Component\Process\Process;

bladeFixtureTest('tailwind');

it('discovers Tailwind v4 theme utilities from the default stylesheet', function () {
    $tmp = base_path('tests/.tailwind-v4-'.bin2hex(random_bytes(6)));
    $stylesheet = base_path('resources/css/app.css');
    $view = $tmp.'/resources/views/components/button.blade.php';

    @mkdir(dirname($stylesheet), 0777, true);
    @mkdir(dirname($view), 0777, true);

    file_put_contents($stylesheet, <<<'CSS'
    @import 'tailwindcss';

    @theme {
        --color-brand: oklch(0.6 0.2 250);
    }
    CSS);

    try {
        file_put_contents($view, '<div class="text-white bg-brand p-4 flex"></div>'."\n");

        runPintBlade($tmp);

        expect(file_get_contents($view))->toBe('<div class="flex bg-brand p-4 text-white"></div>'."\n");
    } finally {
        removeBladeTempDirectory($tmp);
        @unlink($stylesheet);
        @rmdir(dirname($stylesheet));
    }
});

it('refreshes cached Blade formatting after the default Tailwind stylesheet changes', function ($before, $after, $expectedBefore, $expectedAfter) {
    $tmp = base_path('tests/.tailwind-v4-cache-'.bin2hex(random_bytes(6)));
    $stylesheet = base_path('resources/css/app.css');
    $view = $tmp.'/resources/views/button.blade.php';

    @mkdir(dirname($stylesheet), 0777, true);
    @mkdir(dirname($view), 0777, true);

    $format = function () use ($tmp) {
        $process = new Process(['php', 'pint', '--blade', '--cache-file', $tmp.'/.pint.cache', '--config', $tmp.'/pint.json', $tmp], base_path());
        $process->setTimeout(120);
        $process->mustRun();

        expect($tmp.'/.pint.cache')->toBeFile();
    };

    try {
        if ($before !== null) {
            file_put_contents($stylesheet, $before);
        }

        file_put_contents($tmp.'/pint.json', '{"preset":"laravel"}'."\n");
        file_put_contents($view, '<div class="text-white bg-brand p-4 flex"></div>'."\n");

        $format();
        $format();

        expect(file_get_contents($view))->toBe($expectedBefore."\n");

        $after === null ? unlink($stylesheet) : file_put_contents($stylesheet, $after);

        $format();

        expect(file_get_contents($view))->toBe($expectedAfter."\n");
    } finally {
        removeBladeTempDirectory($tmp);
        @unlink($stylesheet);
        @rmdir(dirname($stylesheet));
    }
})->with([
    'created' => [
        null,
        "@import 'tailwindcss'; @theme { --color-brand: oklch(0.6 0.2 250); }",
        '<div class="bg-brand flex p-4 text-white"></div>',
        '<div class="flex bg-brand p-4 text-white"></div>',
    ],
    'updated' => [
        "@import 'tailwindcss';",
        "@import 'tailwindcss'; @theme { --color-brand: oklch(0.6 0.2 250); }",
        '<div class="bg-brand flex p-4 text-white"></div>',
        '<div class="flex bg-brand p-4 text-white"></div>',
    ],
    'removed' => [
        "@import 'tailwindcss'; @theme { --color-brand: oklch(0.6 0.2 250); }",
        null,
        '<div class="flex bg-brand p-4 text-white"></div>',
        '<div class="bg-brand flex p-4 text-white"></div>',
    ],
]);
