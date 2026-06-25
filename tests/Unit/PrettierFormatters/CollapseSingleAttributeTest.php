<?php

use App\PrettierFormatters\CollapseSingleAttribute;

it('collapses a tag with a single wrapped attribute', function () {
    $in = "<section\n    class=\"a b c\"\n>\n    Hello\n</section>\n";
    $out = "<section class=\"a b c\">\n    Hello\n</section>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses a self-closing tag with a single wrapped attribute', function () {
    $in = "<input\n    class=\"a b c\"\n/>\n";
    $out = "<input class=\"a b c\" />\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('preserves indentation when collapsing', function () {
    $in = "        <div\n            class=\"a b c\"\n        >\n";
    $out = "        <div class=\"a b c\">\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses past the print width', function () {
    $value = str_repeat('x', 200);
    $in = "<div\n    class=\"{$value}\"\n>\n";
    $out = "<div class=\"{$value}\">\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses namespaced component and directive attributes', function () {
    $in = "<flux:input\n    wire:model=\"search\"\n/>\n";
    $out = "<flux:input wire:model=\"search\" />\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('never collapses the block shape with more than one attribute', function () {
    $in = "<div\n    class=\"a b c\"\n    id=\"main\"\n>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('collapses the hugging shape with the attribute on the tag line', function () {
    $in = "<a class=\"a b c\"\n    >link text</a\n>\n";
    $out = "<a class=\"a b c\">link text</a>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses a hugging tag with a directive attribute', function () {
    $in = "<div @class(['a', 'b' => \$x])\n    >text</div\n>\n";
    $out = "<div @class(['a', 'b' => \$x])>text</div>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses a hugging tag with nested child markup as content', function () {
    $in = "<a class=\"x\"\n    ><span>y</span></a\n>\n";
    $out = "<a class=\"x\"><span>y</span></a>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('never collapses the hugging shape with more than one attribute', function () {
    $in = "<a href=\"#\" class=\"x\"\n    >link text</a\n>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('collapses an empty element whose terminator and close share a line', function () {
    $in = "<div\n    class=\"a b c\"\n></div>\n";
    $out = "<div class=\"a b c\"></div>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses an attribute-on-own-line tag with hugged content and a separate close', function () {
    $in = "<div\n    @class(['a', 'b' => \$x])\n    >hugged\n</div>\n";
    $out = "<div @class(['a', 'b' => \$x])>hugged</div>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('collapses an attribute-on-own-line tag with all content on the terminator line', function () {
    $in = "<span\n    class=\"a b c\"\n    >hello</span>\n";
    $out = "<span class=\"a b c\">hello</span>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('never collapses a multi-line attribute value', function () {
    $in = "<div\n    x-data=\"{\n        open: false,\n    }\"\n>\n    body\n</div>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('hugs a wrapped multi-line directive attribute back onto the tag', function () {
    $in = "<div\n    @class([\n        'a' => \$x,\n        'b' => \$y,\n    ])\n>\n    body\n</div>\n";
    $out = "<div @class([\n    'a' => \$x,\n    'b' => \$y,\n])>\n    body\n</div>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('preserves indentation when hugging a wrapped directive attribute', function () {
    $in = "    <div\n        @class([\n            'a' => \$x,\n        ])\n    >\n";
    $out = "    <div @class([\n        'a' => \$x,\n    ])>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('hugs a wrapped self-closing directive attribute', function () {
    $in = "<input\n    @class([\n        'a' => \$x,\n    ])\n/>\n";
    $out = "<input @class([\n    'a' => \$x,\n]) />\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('hugs a wrapped multi-line echo attribute', function () {
    $in = "<div\n    {{\n        \$attributes->merge(['class' => 'x'])\n    }}\n>\n";
    $out = "<div {{\n    \$attributes->merge(['class' => 'x'])\n}}>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($out);
});

it('never hugs a wrapped directive when another attribute follows', function () {
    $in = "<div\n    @class([\n        'a' => \$x,\n    ])\n    id=\"main\"\n>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('is idempotent on an already-hugged directive attribute', function () {
    $in = "<div @class([\n    'a' => \$x,\n])>\n    body\n</div>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('is idempotent on an already-collapsed tag', function () {
    $in = "<section class=\"a b c\">\n    Hello\n</section>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('does not touch a closing tag', function () {
    $in = "</section\n>\n";

    expect((new CollapseSingleAttribute)->postFormat($in))->toBe($in);
});

it('counts quoted, bracketed, and braced attributes as single tokens', function () {
    $formatter = new CollapseSingleAttribute;
    $reflection = new ReflectionMethod($formatter, 'attributeCount');

    expect($reflection->invoke($formatter, 'class="a b c"'))->toBe(1)
        ->and($reflection->invoke($formatter, "@class(['a', 'b' => \$x])"))->toBe(1)
        ->and($reflection->invoke($formatter, '{{ $attributes->merge([]) }}'))->toBe(1)
        ->and($reflection->invoke($formatter, 'href="#" class="x"'))->toBe(2)
        ->and($reflection->invoke($formatter, 'class="unbalanced'))->toBe(-1)
        ->and($reflection->invoke($formatter, 'class="x">'))->toBe(-1);
});
