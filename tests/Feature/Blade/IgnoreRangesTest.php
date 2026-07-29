<?php

use App\BladeFormatter;
use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\PrettierException;
use App\Support\Prettier;

class CorruptingIgnoreRangeFormatter implements PrettierPostFormatter, PrettierPreFormatter
{
    public function preFormat(string $content): string
    {
        return $content.'INTERMEDIATE';
    }

    public function postFormat(string $content): string
    {
        return str_replace('__PINT_BLADE_IGNORE_0__', '', $content);
    }
}

class BladeFormatterWithCorruptingIgnoreRangeFormatter extends BladeFormatter
{
    protected static array $formatters = [
        CorruptingIgnoreRangeFormatter::class,
    ];
}

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

it('rejects invalid ignore ranges returned by the worker', function () {
    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('ignoreRanges')->once()->andReturn([
        ['start' => 0, 'end' => 100],
    ]);

    (new BladeFormatter($prettier))->format('invalid.blade.php', '<div></div>');
})->throws(PrettierException::class, 'Laravel Pint\'s Prettier worker returned invalid Blade ignore ranges.');

it('returns pristine input if an ignore range placeholder is corrupted', function () {
    $in = "{{-- format-ignore-start --}}\nraw\n{{-- format-ignore-end --}}\n";
    $end = strlen($in);
    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('ignoreRanges')->once()->andReturn([
        ['start' => 0, 'end' => $end],
    ]);
    $prettier->shouldReceive('formatWithIgnoreRanges')->once()->andReturn([
        'formatted' => $in.'INTERMEDIATE',
        'ranges' => [
            ['start' => 0, 'end' => $end, 'sourceStart' => 0, 'sourceEnd' => $end],
        ],
    ]);

    expect((new BladeFormatterWithCorruptingIgnoreRangeFormatter($prettier))->format('fallback.blade.php', $in))->toBe($in);
});
