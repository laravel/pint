<?php

use App\PrettierFormatters\JoinDanglingCloseBracket;

it('joins a dangling close bracket onto the close tag', function () {
    $in = "<a\n    href=\"#\"\n    class=\"x\"\n    >Changelog</a\n>\n";
    $out = "<a\n    href=\"#\"\n    class=\"x\"\n    >Changelog</a>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($out);
});

it('preserves the close tag indentation when joining', function () {
    $in = "<div>\n    <span\n        class=\"x\"\n        >hello</span\n    >\n</div>\n";
    $out = "<div>\n    <span\n        class=\"x\"\n        >hello</span>\n</div>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($out);
});

it('joins a bare dangling close tag with no content', function () {
    $in = "</section\n>\n";
    $out = "</section>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($out);
});

it('joins namespaced component close tags', function () {
    $in = "<x-slot:title\n    class=\"x\"\n    >Title</x-slot:title\n>\n";
    $out = "<x-slot:title\n    class=\"x\"\n    >Title</x-slot:title>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($out);
});

it('leaves an opening tag terminator on its own line untouched', function () {
    $in = "<div\n    class=\"a\"\n    id=\"b\"\n>\n    body\n</div>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($in);
});

it('leaves the content-hugging opening terminator untouched', function () {
    $in = "<textarea\n    >{{ \$content }}</textarea>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($in);
});

it('is idempotent on an already-joined close tag', function () {
    $in = "<a\n    href=\"#\"\n    class=\"x\"\n    >Changelog</a>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($in);
});

it('does not join a dangling bracket that does not follow a close tag', function () {
    $in = "<a\n    href=\"#\"\n    class=\"x\"\n>\n";

    expect((new JoinDanglingCloseBracket)->postFormat($in))->toBe($in);
});
