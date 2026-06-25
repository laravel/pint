<?php

use App\PrettierFormatters\DedentHuggedTerminator;

it('dedents a hugged terminator to its opening tag indent', function () {
    $in = "    <a\n        href=\"#\"\n        class=\"link\"\n        >Changelog</a>\n";
    $out = "    <a\n        href=\"#\"\n        class=\"link\"\n    >Changelog</a>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($out);
});

it('dedents an empty hugged terminator', function () {
    $in = "<a\n    href=\"#\"\n    class=\"link\"\n    ></a>\n";
    $out = "<a\n    href=\"#\"\n    class=\"link\"\n></a>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($out);
});

it('leaves an already-aligned terminator untouched (idempotent)', function () {
    $in = "    <a\n        href=\"#\"\n    >Changelog</a>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($in);
});

it('never touches a bare block-form terminator with content beneath it', function () {
    $in = "    <div\n        class=\"box\"\n        >\n        {{ \$slot }}\n    </div>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($in);
});

it('never touches a single-line element', function () {
    $in = "    <a href=\"#\">Changelog</a>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($in);
});

it('handles namespaced and hyphenated tag names', function () {
    $in = "<x-link\n    :href=\"\$url\"\n        >Go</x-link>\n";
    $out = "<x-link\n    :href=\"\$url\"\n>Go</x-link>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($out);
});

it('leaves a wrapped opening tag with multi-line hugged content alone', function () {
    $in = "    <a\n        href=\"#\"\n        >first\n        second</a>\n";

    expect((new DedentHuggedTerminator)->postFormat($in))->toBe($in);
});
