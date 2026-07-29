<?php

use App\BladeFormatter;

bladeFixtureTest('ignore-ranges');

it('preserves a UTF-8 byte order mark when formatting ignore ranges', function () {
    $input = "\u{FEFF}<div  id=\"before\"></div>\n"
        ."{{-- format-ignore-start --}}\n"
        ."@php    \$x = !\$y; @endphp\n"
        ."{{-- format-ignore-end --}}\n"
        ."<div  id=\"after\"></div>\n";
    $expected = "\u{FEFF}<div id=\"before\"></div>\n"
        ."{{-- format-ignore-start --}}\n"
        ."@php    \$x = !\$y; @endphp\n"
        ."{{-- format-ignore-end --}}\n"
        ."<div id=\"after\"></div>\n";

    $formatted = app(BladeFormatter::class)->format('bom.blade.php', $input);

    expect($formatted)->toBe($expected);
});
