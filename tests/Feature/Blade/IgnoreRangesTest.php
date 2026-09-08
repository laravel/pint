<?php

use App\BladeFormatter;
use App\PrettierFormatters\EmbeddedBladeMasker;
use App\Support\Prettier;

bladeFixtureTest('ignore-ranges');

it('preserves all supported ignore marker variants case-insensitively', function () {
    $in = "{{-- format-ignore-start --}}\n"
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
    $out = "{{-- format-ignore-start --}}\n"
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
        ."<div id=\"after\"></div>\n";

    expect(app(BladeFormatter::class)->format('markers.blade.php', $in))->toBe($out);
});

it('preserves byte-sensitive ignored content while formatting its surroundings', function () {
    $in = "\u{FEFF}<p>𠮷</p>\r\n"
        ."<div  id=\"before\"></div>\n"
        ."{{-- format-ignore-start --}}\r\n"
        ."𠮷 __PINT_BLADE_IGNORE_0__  \r\n"
        ."{\"formatted\":\"x\"} [PINT_PRETTIER_WORKER]\r\n"
        ."@php    \$x = !\$y; @endphp\n"
        ."{{-- format-ignore-end --}}\r\n"
        ."<div  id=\"after\"></div>\r\n";
    $out = "\u{FEFF}<p>𠮷</p>\n"
        ."<div id=\"before\"></div>\n"
        ."{{-- format-ignore-start --}}\r\n"
        ."𠮷 __PINT_BLADE_IGNORE_0__  \r\n"
        ."{\"formatted\":\"x\"} [PINT_PRETTIER_WORKER]\r\n"
        ."@php    \$x = !\$y; @endphp\n"
        ."{{-- format-ignore-end --}}\n"
        ."<div id=\"after\"></div>\n";

    expect(app(BladeFormatter::class)->format('bytes.blade.php', $in))->toBe($out);
});

it('safely handles malformed and non-marker occurrences', function (string $in, string $out) {
    expect(app(BladeFormatter::class)->format('malformed.blade.php', $in))->toBe($out);
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

it('formats surrounding content when an ignore range is nested in an embedded directive block', function (string $element, string $ignored) {
    $formatter = new class(app(Prettier::class)) extends BladeFormatter
    {
        protected static array $formatters = [
            EmbeddedBladeMasker::class,
        ];
    };
    $ignoreRange = "{{-- format-ignore-start --}}\n"
        .$ignored."\n"
        .'{{-- format-ignore-end --}}';
    $visibleIgnoreRange = "{{-- prettier-ignore-start --}}\n"
        ."<section  id=\"visible\"></section>\n"
        .'{{-- prettier-ignore-end --}}';
    $in = "<{$element}>\n"
        ."@if(\$ready)\n"
        .$ignoreRange."\n"
        ."@endif\n"
        ."</{$element}>\n"
        ."<div  id=\"after\"></div>\n"
        .$visibleIgnoreRange."\n";

    $formatted = $formatter->format('nested.blade.php', $in);

    expect($formatted)->toContain($ignoreRange)
        ->toContain($visibleIgnoreRange)
        ->toContain('<div id="after"></div>');
    expect($formatter->format('nested.blade.php', $formatted))->toBe($formatted);
})->with([
    'script' => ['script', 'const  keep = [1,  2];'],
    'script with token-like content' => ['script', 'const  keep = "__PINT_BLADE_IGNORE_2__";'],
    'style' => ['style', '.keep  { color:  red; }'],
]);
