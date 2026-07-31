<?php

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\UnrestorableContentException;
use App\PrettierFormatters\EmbeddedBladeMasker;

/**
 * Read the private placeholder => original map off a masker instance.
 *
 * @return array<string, string>
 */
function maskerMap(EmbeddedBladeMasker $masker): array
{
    return (function () {
        /** @var EmbeddedBladeMasker $this */
        return $this->map;
    })->call($masker);
}

/**
 * Read the private escaped-"@@" placeholder => occurrence-count map off a masker instance.
 *
 * @return array<string, int>
 */
function maskerEscapedCounts(EmbeddedBladeMasker $masker): array
{
    return (function () {
        /** @var EmbeddedBladeMasker $this */
        return $this->escapedCounts;
    })->call($masker);
}

it('implements both the pre and post formatter contracts', function () {
    $masker = new EmbeddedBladeMasker;

    expect($masker)->toBeInstanceOf(PrettierPreFormatter::class);
    expect($masker)->toBeInstanceOf(PrettierPostFormatter::class);
});

it('replaces a construct inside <style> with a valid placeholder and builds a correct map', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<style>\n.brand { color: @php echo \$brand; @endphp; }\n</style>";
    $out = $masker->preFormat($in);

    $map = maskerMap($masker);

    expect($map)->toHaveCount(1);
    expect(array_keys($map)[0])->toBe('__PINT_BLADE_0__');
    expect(array_values($map)[0])->toBe('@php echo $brand; @endphp');
    expect($out)->toBe("<style>\n.brand { color: __PINT_BLADE_0__; }\n</style>");
});

it('does not let a ">" inside an opening-tag attribute value end the tag early', function () {
    $masker = new EmbeddedBladeMasker;

    $in = '<script type="text/x-template" data-cmp="a>b">let x = @if ($ok) 1 @else 2 @endif;</script>';
    $out = $masker->preFormat($in);

    $map = maskerMap($masker);

    // The whole opening tag (including the ">" inside data-cmp) must be carried
    // through untouched, and only the Blade in the body masked.
    expect($out)->toStartWith('<script type="text/x-template" data-cmp="a>b">')
        ->and($out)->toEndWith('</script>')
        ->and($map)->toHaveCount(1)
        ->and(array_values($map)[0])->toBe('@if ($ok) 1 @else 2 @endif');
});

it('leaves Blade in normal HTML untouched', function () {
    $masker = new EmbeddedBladeMasker;

    $in = '<div>@if ($ok) <span>{{ $name }}</span> @endif</div>';
    $out = $masker->preFormat($in);

    expect($out)->toBe($in);
    expect(maskerMap($masker))->toBe([]);
});

it('is a no-op with an empty map when nothing qualifies', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<style>\n.brand { color: red; }\n</style>\n<script>\nconst a = 1;\n</script>";
    $out = $masker->preFormat($in);

    expect($out)->toBe($in);
    expect(maskerMap($masker))->toBe([]);
    // postFormat must be a pure pass-through when the map is empty.
    expect($masker->postFormat('anything at all'))->toBe('anything at all');
});

it('round-trips every original construct exactly on the same instance', function () {
    $masker = new EmbeddedBladeMasker;

    $in = implode("\n", [
        '<style>',
        '.a { color: @php echo $a; @endphp; }',
        '@if ($dark) .b { color: black; } @endif',
        '.c { width: <?php echo $w; ?>px; }',
        '</style>',
        '<script>',
        '@php echo "x"; @endphp',
        'const total = <?php echo $t; ?>;',
        '</script>',
    ]);

    // preFormat then postFormat on the SAME instance must restore byte-for-byte
    // (prettier is a no-op here, so only the masker's two passes apply).
    $masked = $masker->preFormat($in);

    expect($masked)->not->toBe($in);
    expect($masker->postFormat($masked))->toBe($in);

    // Every recorded original must be a verbatim slice of the source.
    foreach (maskerMap($masker) as $original) {
        expect($in)->toContain($original);
    }
});

it('masks a whole string literal that embeds a Blade echo in <script>', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<script>\nwidget.boot(\"{{ config('a') }}\", \"{{ config('b') }}\");\n</script>";
    $out = $masker->preFormat($in);

    $map = maskerMap($masker);

    // The quotes are part of the masked token, so prettier can neither rewrite
    // them to single quotes nor trip over the single quotes inside the echo.
    expect($map)->toBe([
        '__PINT_BLADE_0__' => '"{{ config(\'a\') }}"',
        '__PINT_BLADE_1__' => '"{{ config(\'b\') }}"',
    ]);
    expect($out)->toBe("<script>\nwidget.boot(__PINT_BLADE_0__, __PINT_BLADE_1__);\n</script>");
    expect($masker->postFormat($out))->toBe($in);
});

it('masks an echo whose nested quotes would otherwise end the CSS string early', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<style>\n@font-face { src: url('{{ asset('a.woff2') }}'); }\n</style>";
    $out = $masker->preFormat($in);

    expect(maskerMap($masker))->toBe([
        '__PINT_BLADE_0__' => "'{{ asset('a.woff2') }}'",
    ]);
    expect($out)->toBe("<style>\n@font-face { src: url(__PINT_BLADE_0__); }\n</style>");
    expect($masker->postFormat($out))->toBe($in);
});

it('masks a bare Blade echo outside any string literal', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<script>\nconst id = {{ \$id }};\n</script>";
    $out = $masker->preFormat($in);

    expect(maskerMap($masker))->toBe(['__PINT_BLADE_0__' => '{{ $id }}']);
    expect($out)->toBe("<script>\nconst id = __PINT_BLADE_0__;\n</script>");
    expect($masker->postFormat($out))->toBe($in);
});

it('leaves a plain string literal with no Blade untouched', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<script>\nconst label = '@click is fine here';\n</script>";
    $out = $masker->preFormat($in);

    expect($out)->toBe($in);
    expect(maskerMap($masker))->toBe([]);
});

it('selects a bare identifier in CSS value context', function () {
    $masker = new EmbeddedBladeMasker;

    $masker->preFormat("<style>\n.a { color: @php echo \$x; @endphp; }\n</style>");

    expect(array_keys(maskerMap($masker))[0])->toBe('__PINT_BLADE_0__');
});

it('selects a custom property in CSS statement context', function () {
    $masker = new EmbeddedBladeMasker;

    $masker->preFormat("<style>\n@if (\$dark) .a { color: black; } @endif\n</style>");

    expect(array_keys(maskerMap($masker))[0])->toBe('--pint-blade-0: 1;');
});

it('appends a semicolon for a JS statement but not a JS expression', function () {
    $statement = new EmbeddedBladeMasker;
    $statement->preFormat("<script>\n@php echo 'x'; @endphp\n</script>");
    expect(array_keys(maskerMap($statement))[0])->toBe('__PINT_BLADE_0__;');

    $expression = new EmbeddedBladeMasker;
    $expression->preFormat("<script>\nconst t = <?php echo \$t; ?>;\n</script>");
    expect(array_keys(maskerMap($expression))[0])->toBe('__PINT_BLADE_0__');
});

it('re-salts the token index when the source already contains a placeholder-like string', function () {
    $masker = new EmbeddedBladeMasker;

    // The literal "__PINT_BLADE_0__" appears in the HTML body, so index 0 must
    // be skipped to keep the token unique against the source.
    $in = "<p>__PINT_BLADE_0__</p>\n<style>\n.a { color: @php echo \$x; @endphp; }\n</style>";
    $out = $masker->preFormat($in);

    $map = maskerMap($masker);

    expect(array_keys($map)[0])->toBe('__PINT_BLADE_1__');
    expect($out)->toContain('__PINT_BLADE_0__</p>'); // the source literal is untouched
    expect($masker->postFormat($out))->toBe($in);    // and the round-trip is exact
});

it('masks an escaped "@@" inside a string literal', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<script>\nconst email = \"support@@example.com\";\n</script>";
    $out = $masker->preFormat($in);

    // The placeholder leads with a digit, so it is neither a valid identifier nor a valid
    // number and prettier's "quoteProps: as-needed" cannot drop the quotes around it when
    // the string sits in an object-key position.
    expect($out)->toBe("<script>\nconst email = \"support0zexample.com\";\n</script>");
    expect(maskerEscapedCounts($masker))->toBe(['0z' => 1]);
    expect(maskerMap($masker))->toBe([]);
    expect($masker->postFormat($out))->toBe($in);
});

it('masks an escaped "@@" outside any string literal', function () {
    $masker = new EmbeddedBladeMasker;

    // Prettier's blade plugin re-indents the raw-text block around a bare "@@" a little
    // further on every pass, so it has to be masked even though it is not a directive.
    $in = "<script>\n// reach us at support@@example.com\nconst a = 1;\n</script>";
    $out = $masker->preFormat($in);

    expect($out)->toBe("<script>\n// reach us at supportzzexample.com\nconst a = 1;\n</script>");
    expect(maskerEscapedCounts($masker))->toBe(['zz' => 1]);
    expect($masker->postFormat($out))->toBe($in);
});

it('masks an escaped "@@" without changing the width of the line', function () {
    $masker = new EmbeddedBladeMasker;

    // Both placeholders are exactly as wide as the "@@" they replace, so prettier measures
    // the real line length and makes the wrapping decision it would have made unmasked.
    foreach (['bare' => '@@', 'quoted' => '"@@"'] as $escape) {
        $in = "<script>\nconst a = {$escape};\n</script>";
        $out = $masker->preFormat($in);

        expect($out)->not->toContain('@@'); // the escape really was masked
        expect(strlen($out))->toBe(strlen($in));
    }
});

it('shares a single placeholder across every escaped "@@" and restores them all', function () {
    $masker = new EmbeddedBladeMasker;

    $in = implode("\n", [
        '<script>',
        '// a bare @@ and another @@',
        'const a = "one @@ here";',
        'const b = "two @@ and @@ here";',
        'const c = "a doubled @@@@ here";',
        '</script>',
    ]);

    $out = $masker->preFormat($in);

    // One placeholder per shape, each carrying the number of escapes it stands in for:
    // two bare, and five quoted (1 + 2 + 2 for the doubled pair).
    expect(maskerEscapedCounts($masker))->toBe(['zz' => 2, '0z' => 5]);
    expect($out)->not->toContain('@@');
    expect($masker->postFormat($out))->toBe($in);
});

it('widens the escaped-"@@" placeholder when the source already contains it', function () {
    $masker = new EmbeddedBladeMasker;

    // Both default placeholders appear verbatim in the HTML body, so each has to grow until
    // it is unique against the source.
    $in = "<p>zz and 0z</p>\n<script>\nconst a = \"x@@y\";\n// bare @@ too\n</script>";
    $out = $masker->preFormat($in);

    expect(maskerEscapedCounts($masker))->toBe(['0zz' => 1, 'zzz' => 1]);
    expect($out)->toContain('<p>zz and 0z</p>'); // the source literals are untouched
    expect($masker->postFormat($out))->toBe($in); // and the round-trip is exact
});

it('bails out when an escaped "@@" does not come back intact', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<script>\nconst a = \"@@one\";\nconst b = \"@@two\";\n</script>";
    $masker->preFormat($in);

    // Two escapes were masked but only one placeholder comes back. Rather than emit a
    // half-restored file, postFormat gives up on the run.
    $masker->postFormat("<script>\nconst a = '0zone';\n</script>");
})->throws(UnrestorableContentException::class);

it('bails out when a token cannot be restored', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<style>\n.a { color: @php echo \$x; @endphp; }\n</style>";
    $masker->preFormat($in);

    // A token is missing from the content handed to postFormat. Restoring what is left
    // would emit a file the masker can no longer vouch for, so it gives up instead and
    // BladeFormatter hands back the untouched original.
    $masker->postFormat("<style>\n.a { color: red; }\n</style>");
})->throws(UnrestorableContentException::class);
