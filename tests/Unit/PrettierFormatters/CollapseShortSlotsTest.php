<?php

use App\PrettierFormatters\CollapseShortSlots;

it('collapses a short named slot', function () {
    $in = "<x-slot name=\"title\">\n    Bookmarks\n</x-slot>\n";
    $out = "<x-slot name=\"title\">Bookmarks</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($out);
});

it('collapses the shorthand slot syntax', function () {
    $in = "<x-slot:heading>\n    Dashboard\n</x-slot:heading>\n";
    $out = "<x-slot:heading>Dashboard</x-slot:heading>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($out);
});

it('collapses a body containing an echo', function () {
    $in = "<x-slot name=\"count\">\n    {{ \$count }} items\n</x-slot>\n";
    $out = "<x-slot name=\"count\">{{ \$count }} items</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($out);
});

it('preserves indentation when collapsing', function () {
    $in = "        <x-slot name=\"title\">\n            Bookmarks\n        </x-slot>\n";
    $out = "        <x-slot name=\"title\">Bookmarks</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($out);
});

it('is idempotent on an already-collapsed slot', function () {
    $in = "<x-slot name=\"title\">Bookmarks</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});

it('never collapses a body containing child markup', function () {
    $in = "<x-slot name=\"body\">\n    Some <a href=\"#\">link</a>\n</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});

it('never collapses a body containing a blade directive', function () {
    $in = "<x-slot name=\"d\">\n    @if (\$x) yes @endif\n</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});

it('never collapses a multi-line body', function () {
    $in = "<x-slot name=\"body\">\n    line one\n    line two\n</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});

it('does not collapse when the result would exceed the print width', function () {
    $body = str_repeat('x', 130);
    $in = "<x-slot name=\"long\">\n    {$body}\n</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});

it('does not collapse a slot whose opening tag is wrapped over multiple lines', function () {
    $in = "<x-slot\n    name=\"title\"\n>\n    Bookmarks\n</x-slot>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});

it('does not touch other elements', function () {
    $in = "<div>\n    Hello\n</div>\n";

    expect((new CollapseShortSlots)->postFormat($in))->toBe($in);
});
