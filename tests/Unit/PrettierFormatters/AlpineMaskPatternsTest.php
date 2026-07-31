<?php

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\UnrestorableContentException;
use App\PrettierFormatters\AlpineMaskPatterns;

/**
 * Read the private placeholder => original map off a formatter instance.
 *
 * @return array<string, string>
 */
function maskPatternsMap(AlpineMaskPatterns $formatter): array
{
    return (function () {
        /** @var AlpineMaskPatterns $this */
        return $this->map;
    })->call($formatter);
}

it('implements both the pre and post formatter contracts', function () {
    $formatter = new AlpineMaskPatterns;

    expect($formatter)->toBeInstanceOf(PrettierPreFormatter::class);
    expect($formatter)->toBeInstanceOf(PrettierPostFormatter::class);
});

it('masks a pattern with a placeholder and restores it byte for byte', function () {
    $formatter = new AlpineMaskPatterns;

    $in = '<input x-mask="9999-999" placeholder="1234-567" />';
    $out = $formatter->preFormat($in);

    expect(maskPatternsMap($formatter))->toBe(['pm0_____' => '9999-999']);
    expect($out)->toBe('<input x-mask="pm0_____" placeholder="1234-567" />');
    expect($formatter->postFormat($out))->toBe($in);
});

it('pads the placeholder to the pattern width so prettier wraps the tag the same way', function (string $value) {
    $formatter = new AlpineMaskPatterns;

    $formatter->preFormat('<input x-mask="'.$value.'" />');

    // Prettier measures the masked line, so a placeholder of a different width would move
    // the tag across the "printWidth" boundary and wrap (or unwrap) it for the wrong reason.
    expect(strlen(array_key_first(maskPatternsMap($formatter))))->toBe(strlen($value));
})->with(['9999', '9999-999', '(999) 999-9999', '99/99/9999 99:99']);

it('keeps the placeholder a valid identifier when the pattern is shorter than the token', function () {
    $formatter = new AlpineMaskPatterns;

    $out = $formatter->preFormat('<input x-mask="a" />');

    expect($out)->toBe('<input x-mask="pm0_" />');
    expect($formatter->postFormat($out))->toBe('<input x-mask="a" />');
});

it('masks every quoting style, including an unquoted value', function (string $attribute, string $value) {
    $formatter = new AlpineMaskPatterns;

    $in = '<input '.$attribute.' />';
    $out = $formatter->preFormat($in);

    expect(maskPatternsMap($formatter))->toBe([str_pad('pm0_', max(4, strlen($value)), '_') => $value]);
    expect($formatter->postFormat($out))->toBe($in);
})->with([
    ['x-mask="9999-999"', '9999-999'],
    ["x-mask='9999-999'", '9999-999'],
    ['x-mask=9999-999', '9999-999'],
    ['x-mask = "9999-999"', '9999-999'],
    ['X-MASK="9999-999"', '9999-999'],
    ['x-mask.foo="9999-999"', '9999-999'],
    ['x-mask="{{ $mask }}"', '{{ $mask }}'],
]);

it('leaves an attribute that does not hold a literal pattern untouched', function (string $attribute) {
    $formatter = new AlpineMaskPatterns;

    $in = '<input '.$attribute.' />';

    expect($formatter->preFormat($in))->toBe($in);
    expect(maskPatternsMap($formatter))->toBe([]);
})->with([
    // "x-mask:dynamic" really does hold a JS expression, so prettier must format it.
    ['x-mask:dynamic="$money($input)"'],
    ['data-x-mask="9999-999"'],
    ['wire:x-mask="9999-999"'],
    [':x-mask="$mask"'],
    ['x-model="form.a-b"'],
    ['x-mask=""'],
]);

it('mints one placeholder per occurrence, even for repeated patterns', function () {
    $formatter = new AlpineMaskPatterns;

    $in = '<input x-mask="9999-999" /><input x-mask="9999-999" />';
    $out = $formatter->preFormat($in);

    expect(maskPatternsMap($formatter))->toBe([
        'pm0_____' => '9999-999',
        'pm1_____' => '9999-999',
    ]);
    expect($formatter->postFormat($out))->toBe($in);
});

it('widens the placeholder when the source already contains it', function () {
    $formatter = new AlpineMaskPatterns;

    // The literal "pm0_____" appears in the body, so the placeholder must grow to stay
    // unique against the source, even though that costs the exact width match.
    $in = '<p>pm0_____</p><input x-mask="9999-999" />';
    $out = $formatter->preFormat($in);

    expect(array_key_first(maskPatternsMap($formatter)))->toBe('pm0______');
    expect($out)->toContain('<p>pm0_____</p>');
    expect($formatter->postFormat($out))->toBe($in);
});

it('is a pure pass-through when nothing was masked', function () {
    $formatter = new AlpineMaskPatterns;

    $in = '<div><input type="text" /></div>';

    expect($formatter->preFormat($in))->toBe($in);
    expect(maskPatternsMap($formatter))->toBe([]);
    expect($formatter->postFormat('anything at all'))->toBe('anything at all');
});

it('bails out instead of half-restoring when a placeholder does not come back', function () {
    $formatter = new AlpineMaskPatterns;

    $formatter->preFormat('<input x-mask="9999-999" />');

    // The placeholder is gone from the content handed to postFormat. Restoring what is
    // left would emit a file the formatter can no longer vouch for.
    $formatter->postFormat('<input x-mask="9999-999" />');
})->throws(UnrestorableContentException::class);

it('bails out when a placeholder comes back more than once', function () {
    $formatter = new AlpineMaskPatterns;

    $out = $formatter->preFormat('<input x-mask="9999-999" />');

    $formatter->postFormat($out.$out);
})->throws(UnrestorableContentException::class);
