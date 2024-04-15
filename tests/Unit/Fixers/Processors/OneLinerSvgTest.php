<?php

use App\BladeFormatter;

it('post process', function ($before, $after) {
    $formatted = resolve(BladeFormatter::class)->format(
        __DIR__.'../../../Fixtures/fake.blade.php',
        $before,
    );

    expect($formatted)->toBe($after);
})->with([
    [
        <<<'SVG'
            <div>
                <svg><path d="M0 0h24v24H0z" /></svg>
            </div>
            <div>
                <svg><path d="M0 0h24v24H0z" /></svg>
            </div>

            SVG,
        <<<'SVG'
            <div>
                <svg><path d="M0 0h24v24H0z" /></svg>
            </div>
            <div>
                <svg><path d="M0 0h24v24H0z" /></svg>
            </div>

            SVG,
    ],
    [
        <<<'SVG'
            <svg>
                <path d="M0 0h24v24H0z" />
            </svg>
            <svg>
                <path d="M0 0h24v24H0z" />
            </svg>

            SVG,
        <<<'SVG'
            <svg>
                <path d="M0 0h24v24H0z" />
            </svg>
            <svg>
                <path d="M0 0h24v24H0z" />
            </svg>

            SVG,
    ],
    [
        <<<'SVG'
            <svg>
                <path d="M0 0h24v24H0z" />
            </svg>
            <svg>
                <path d="M0 0h24v24H0z" />
                <path d="M0 0h24v24H0z" />
                <path d="M0 0h24v24H0z" />
            </svg>

            SVG,
        <<<'SVG'
            <svg>
                <path d="M0 0h24v24H0z" />
            </svg>
            <svg>
                <path d="M0 0h24v24H0z" /><path d="M0 0h24v24H0z" /><path d="M0 0h24v24H0z" />
            </svg>

            SVG,
    ],
    [
        <<<'SVG'
            <svg
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
                {{ $attributes }}
                class="h-6 w-6 cursor-pointer text-gray-500 transition duration-150 ease-in-out hover:text-gray-700"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                <path d="M1438.64 1177.41c0-.03-.005-.017-.01.004l.01-.004z"></path>
                <path d="M1499.8 976.878c.03-.156-.024-.048-.11.107l.11-.107z" e="M112312.8 976.878c.03-.156-.024-.048-.11.107l.11-.107z" />
                <path d="M1438.64 1177.41c0-.03-.005-.017-.01.004l.01-.004z" />
                <path d="M1499.8 976.878c.03-.156-.024-.048-.11.107l.11-.107z" />
            </svg>

            SVG,
        <<<'SVG'
            <svg
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
                {{ $attributes }}
                class="h-6 w-6 cursor-pointer text-gray-500 transition duration-150 ease-in-out hover:text-gray-700"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path><path d="M1438.64 1177.41c0-.03-.005-.017-.01.004l.01-.004z"></path><path d="M1499.8 976.878c.03-.156-.024-.048-.11.107l.11-.107z" e="M112312.8 976.878c.03-.156-.024-.048-.11.107l.11-.107z" /><path d="M1438.64 1177.41c0-.03-.005-.017-.01.004l.01-.004z" /><path d="M1499.8 976.878c.03-.156-.024-.048-.11.107l.11-.107z" />
            </svg>

            SVG,
    ],
    [
        <<<'SVG'
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                    fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                />
                <path
                    fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                ></path>
            </svg>

            SVG,
        <<<'SVG'
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>

        SVG,
    ],
    [
        <<<'SVG'
                                <div>
                                    <svg
                                        class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>

            SVG,
        <<<'SVG'
            <div>
                <svg
                    class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>

            SVG,
    ],
]);
