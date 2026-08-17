<?php

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
