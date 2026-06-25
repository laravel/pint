<?php

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
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

it('falls back to the original content when a token cannot be restored', function () {
    $masker = new EmbeddedBladeMasker;

    $in = "<style>\n.a { color: @php echo \$x; @endphp; }\n</style>";
    $masker->preFormat($in);

    // A token is missing from the content handed to postFormat. Rather than emit
    // a half-restored (corrupt) file, it returns the original verbatim.
    $corrupted = "<style>\n.a { color: red; }\n</style>";

    expect($masker->postFormat($corrupted))->toBe($in);
});
