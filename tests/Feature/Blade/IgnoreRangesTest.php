<?php

use App\BladeFormatter;

bladeFixtureTest('ignore-ranges');

it('preserves all supported ignore marker variants case-insensitively', function () {
    $input = "{{-- format-ignore-start --}}\n"
        ."@php    \$format = !\$hidden; @endphp\n"
        ."{{-- format-ignore-end --}}\n"
        ."{{-- PRETTIER-IGNORE-START --}}\n"
        ."<div  id=\"blade-prettier\"></div>\n"
        ."{{-- PRETTIER-IGNORE-END --}}\n"
        ."<!-- FORMAT-IGNORE-START -->\n"
        ."<div  id=\"html-format\"></div>\n"
        ."<!-- FORMAT-IGNORE-END -->\n"
        ."<!-- prettier-ignore-start -->\n"
        ."<div  id=\"html-prettier\"></div>\n"
        ."<!-- prettier-ignore-end -->\n"
        ."<div  id=\"after\"></div>\n";
    $expected = str_replace('<div  id="after"></div>', '<div id="after"></div>', $input);

    expect(app(BladeFormatter::class)->format('markers.blade.php', $input))->toBe($expected);
});

it('preserves byte-sensitive ignored content while formatting its surroundings', function () {
    $input = "\u{FEFF}<p>𠮷</p>\r\n"
        ."<div  id=\"before\"></div>\n"
        ."{{-- format-ignore-start --}}\r\n"
        ."𠮷 __PINT_BLADE_IGNORE_0__  \r\n"
        ."@php    \$x = !\$y; @endphp\n"
        ."{{-- format-ignore-end --}}\r\n"
        ."<div  id=\"after\"></div>\r\n";
    $expected = "\u{FEFF}<p>𠮷</p>\n"
        ."<div id=\"before\"></div>\n"
        ."{{-- format-ignore-start --}}\r\n"
        ."𠮷 __PINT_BLADE_IGNORE_0__  \r\n"
        ."@php    \$x = !\$y; @endphp\n"
        ."{{-- format-ignore-end --}}\n"
        ."<div id=\"after\"></div>\n";

    expect(app(BladeFormatter::class)->format('bytes.blade.php', $input))->toBe($expected);
});

it('safely handles malformed and non-marker occurrences', function (string $input, string $expected) {
    expect(app(BladeFormatter::class)->format('malformed.blade.php', $input))->toBe($expected);
})->with([
    'stray end marker' => [
        "{{-- format-ignore-end --}}\n<div  id=\"formatted\"></div>\n",
        "{{-- format-ignore-end --}}\n<div id=\"formatted\"></div>\n",
    ],
    'unclosed range' => [
        "<div  id=\"before\"></div>\n{{-- format-ignore-start --}}\n@php    \$visible = !\$hidden; @endphp\n<div  id=\"preserved\"></div>\n",
        "<div id=\"before\"></div>\n{{-- format-ignore-start --}}\n@php    \$visible = !\$hidden; @endphp\n<div  id=\"preserved\"></div>\n",
    ],
    'start marker in a quoted attribute' => [
        "<div title=\"{{-- format-ignore-start --}}\">content</div>\n<div  id=\"after\">{{  \$value  }}</div>\n",
        "<div title=\"{{-- format-ignore-start --}}\">content</div>\n<div id=\"after\">{{ \$value }}</div>\n",
    ],
]);
