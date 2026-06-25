<?php

use App\PrettierFormatters\StripSensitiveLeadingBlankLines;

it('strips the blank line prettier injects after a wrapped textarea opening tag', function () {
    $in = "<textarea\n    @if (\$x)\n        x-bind=\"b\"\n    @endif\n>\n\n    {{ \$slot }}\n</textarea>\n";
    $out = "<textarea\n    @if (\$x)\n        x-bind=\"b\"\n    @endif\n>\n    {{ \$slot }}\n</textarea>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($out);
});

it('strips every injected blank line so the result is idempotent', function () {
    $in = "<textarea\n    @if (\$x)\n        x-bind=\"b\"\n    @endif\n>\n\n\n\n    {{ \$slot }}\n</textarea>\n";
    $out = "<textarea\n    @if (\$x)\n        x-bind=\"b\"\n    @endif\n>\n    {{ \$slot }}\n</textarea>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($out);
});

it('strips the injected blank line after a wrapped pre opening tag', function () {
    $in = "<pre\n    @class(['code'])\n>\n\n    {{ \$slot }}\n</pre>\n";
    $out = "<pre\n    @class(['code'])\n>\n    {{ \$slot }}\n</pre>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($out);
});

it('strips the injected blank line after a single-line pre tag carrying a directive', function () {
    $in = "<pre @class(['code'])>\n\n\nfn(){}\n</pre>\n";
    $out = "<pre @class(['code'])>\nfn(){}\n</pre>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($out);
});

it('preserves author-written blank lines under a static single-line pre tag', function () {
    // No directive in the tag means prettier never injects, so the blank lines
    // below are the author's intent and must survive untouched.
    $in = "<pre>\n\n\nauthor blanks above\n</pre>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($in);
});

it('leaves a single-line textarea whose content hugs the ">" untouched', function () {
    $in = "<textarea @if (\$r) required @endif>{{ \$v }}</textarea>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($in);
});

it('leaves a wrapped textarea alone when no blank line was injected', function () {
    $in = "<textarea\n    @if (\$x)\n        x-bind=\"b\"\n    @endif\n>\n    {{ \$slot }}\n</textarea>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($in);
});

it('never touches a non-sensitive element such as a div', function () {
    $in = "<div\n    @if (\$x)\n        data-y=\"b\"\n    @endif\n>\n\n    {{ \$slot }}\n</div>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($in);
});

it('never touches blank lines inside a single-line opening tag textarea', function () {
    $in = "<textarea>\n\n    {{ \$slot }}\n</textarea>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($in);
});

it('does not strip a blank line that is genuine content below the first line', function () {
    $in = "<textarea\n    @if (\$x)\n        x-bind=\"b\"\n    @endif\n>\n    first\n\n    second\n</textarea>\n";

    expect((new StripSensitiveLeadingBlankLines)->postFormat($in))->toBe($in);
});
