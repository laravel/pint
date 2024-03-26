<?php

use App\Fixers\LaravelBlade\PostProcessors\RemoveIgnoredCode;

it('pre process and post process', function ($before, $after) {
    $processor = new \App\Fixers\LaravelBlade\Processors\IgnoreCode();

    expect($processor->postProcess($processor->preProcess($before)))->toBe($after);
})->with([
    [
        <<<'HTML'
            <body>
                <style>
                    .foo{color:red}
                </style>
            </body>
            HTML,
        <<<'HTML'
            <body>
                <style>
                    .foo{color:red}
                </style>
            </body>
            HTML,
    ],
    [
        <<<'HTML'
            <body>
                <style>
                    .foo{color:red}
                    .bar{color:blue}
                </style>
            </body>
            HTML,
        <<<'HTML'
            <body>
                <style>
                    .foo{color:red}
                    .bar{color:blue}
                </style>
            </body>
            HTML,
    ],
    [
        <<<'HTML'
            <body>
                <style>
                    .foo{color:red}
                    .bar{color:blue}
                    .baz{color:green}
                </style>
            </body>
            HTML,
        <<<'HTML'
            <body>
                <style>
                    .foo{color:red}
                    .bar{color:blue}
                    .baz{color:green}
                </style>
            </body>
            HTML,
    ],
    [
        <<<'HTML'
            <body>
                <style>
                    /* ! tailwindcss v3.4.1 | MIT License ... */ {
                        color: red;
                    }
                </style>
            </body>
            HTML,
        <<<'HTML'
            <body>
                <style>
                    /* ! tailwindcss v3.4.1 | MIT License ... */ {
                        color: red;
                    }
                </style>
            </body>
            HTML,
    ],
]);
