<?php

use App\PrettierFormatters\JoinDanglingOpenBracket;

it('joins a hugged opening terminator back onto the tag', function () {
    $in = "<flux:subheading\n    >{{ __('hi') }}</flux:subheading>\n";
    $out = "<flux:subheading>{{ __('hi') }}</flux:subheading>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($out);
});

it('preserves the tag indentation when joining', function () {
    $in = "<div>\n    <flux:subheading\n        >Hello</flux:subheading>\n</div>\n";
    $out = "<div>\n    <flux:subheading>Hello</flux:subheading>\n</div>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($out);
});

it('joins an empty element', function () {
    $in = "<flux:subheading\n    ></flux:subheading>\n";
    $out = "<flux:subheading></flux:subheading>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($out);
});

it('leaves a multi-line body untouched', function () {
    $in = "<textarea\n    >{{ \$content }}\n{{ \$signature }}</textarea>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($in);
});

it('leaves a tag with attributes untouched', function () {
    $in = "<a\n    href=\"#\"\n    >Changelog</a>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($in);
});

it('leaves a block-form opening terminator untouched', function () {
    $in = "<div\n>\n    body\n</div>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($in);
});

it('is idempotent on an already-joined element', function () {
    $in = "<flux:subheading>{{ __('hi') }}</flux:subheading>\n";

    expect((new JoinDanglingOpenBracket)->postFormat($in))->toBe($in);
});
