<?php

use App\PrettierFormatters\StripVoidElementSlash;

function stripVoidSlash(string $content): string
{
    return (new StripVoidElementSlash(voidElementSlash: false))->postFormat($content);
}

it('keeps prettier\'s terminator by default', function () {
    $in = "<br />\n<meta charset=\"utf-8\" />\n";

    expect((new StripVoidElementSlash)->postFormat($in))->toBe($in);
});

it('drops the terminator from an inline void element', function () {
    expect(stripVoidSlash("<br />\n"))->toBe("<br>\n");
});

it('drops the terminator from every void element', function (string $tag) {
    expect(stripVoidSlash("<{$tag} />\n"))->toBe("<{$tag}>\n");
})->with(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);

it('drops a terminator glued to the tag name', function () {
    expect(stripVoidSlash("<hr/>\n"))->toBe("<hr>\n");
});

it('keeps the attributes of a void element', function () {
    expect(stripVoidSlash('<meta charset="utf-8" />'))->toBe('<meta charset="utf-8">');
});

it('drops the terminator from a wrapped void element', function () {
    $in = "<input\n    type=\"text\"\n    name=\"email\"\n/>\n";
    $out = "<input\n    type=\"text\"\n    name=\"email\"\n>\n";

    expect(stripVoidSlash($in))->toBe($out);
});

it('is idempotent', function () {
    $in = "<br>\n<img src=\"/logo.png\" alt=\"Logo\">\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('matches the tag name case-insensitively', function () {
    expect(stripVoidSlash('<BR />'))->toBe('<BR>');
});

it('never touches a blade component', function () {
    $in = "<x-icon name=\"star\" />\n<livewire:counter />\n<flux:input wire:model=\"a\" />\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches a self-closing custom element', function () {
    expect(stripVoidSlash('<my-widget />'))->toBe('<my-widget />');
});

it('never touches svg content, where the terminator is meaningful', function () {
    $in = "<svg>\n    <path d=\"M0 0\" />\n    <circle cx=\"1\" cy=\"1\" r=\"1\" />\n</svg>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches a non-void element', function () {
    $in = "<div></div>\n<span>text</span>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside a script', function () {
    $in = "<script>\n    const template = '<br />';\n</script>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside a style', function () {
    $in = "<style>\n    /* <br /> */\n</style>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside a textarea', function () {
    $in = "<textarea><br /></textarea>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside an html comment', function () {
    $in = "<!-- <br /> -->\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside a blade comment', function () {
    $in = "{{-- <br /> --}}\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside a php block', function () {
    $in = "@php\n    \$tag = '<br />';\n@endphp\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches markup inside a php island', function () {
    $in = "<?php \$tag = '<br />'; ?>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('never touches a slash that belongs to an unquoted attribute value', function () {
    $in = "<link href=/css/app.css/>\n";

    expect(stripVoidSlash($in))->toBe($in);
});

it('handles a ">" inside an attribute value', function () {
    expect(stripVoidSlash('<img alt="a > b" src="x" />'))->toBe('<img alt="a > b" src="x">');
});

it('handles a ">" inside a directive argument', function () {
    expect(stripVoidSlash("<img @class(['a' => \$b]) />"))->toBe("<img @class(['a' => \$b])>");
});

it('handles an echo in an attribute value', function () {
    expect(stripVoidSlash('<input value="{{ $a }}" />'))->toBe('<input value="{{ $a }}">');
});

it('still formats after an inline php directive', function () {
    expect(stripVoidSlash("@php(\$a = 1)\n<br />\n"))->toBe("@php(\$a = 1)\n<br>\n");
});

it('leaves an unterminated tag alone', function () {
    $in = "<br\n";

    expect(stripVoidSlash($in))->toBe($in);
});
