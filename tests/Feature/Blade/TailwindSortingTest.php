<?php

it('keeps the tailwind class order when sorting is disabled', function () {
    $tmp = freshBladeTempDirectory();

    file_put_contents($tmp.'/sorting.blade.php', <<<'BLADE'
        <div   class="p-4 flex text-sm bg-white items-center">a</div>
        <div class="foo {{    $extra    }} bar p-4 flex">c</div>

        BLADE);

    runPintBlade($tmp, rules: [
        'Pint/laravel_blade' => ['sort_tailwind_classes' => false],
    ]);

    expect(file_get_contents($tmp.'/sorting.blade.php'))->toBe(<<<'BLADE'
        <div class="p-4 flex text-sm bg-white items-center">a</div>
        <div class="foo {{ $extra }} bar p-4 flex">c</div>

        BLADE);
});

it('sorts the tailwind classes when sorting is enabled', function () {
    $tmp = freshBladeTempDirectory();

    file_put_contents($tmp.'/sorting.blade.php', <<<'BLADE'
        <div   class="p-4 flex text-sm bg-white items-center">a</div>

        BLADE);

    runPintBlade($tmp, rules: [
        'Pint/laravel_blade' => ['sort_tailwind_classes' => true],
    ]);

    expect(file_get_contents($tmp.'/sorting.blade.php'))->toBe(<<<'BLADE'
        <div class="flex items-center bg-white p-4 text-sm">a</div>

        BLADE);
});
