<?php

use App\PrettierFormatters\NotOperatorSpacing;

it('adds a space after a unary "!" in an alpine directive', function () {
    $in = '<button x-show="!recovery"></button>';
    $out = '<button x-show="! recovery"></button>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($out);
});

it('adds a space inside an event handler expression', function () {
    $in = '<div x-on:click="open = !open"></div>';
    $out = '<div x-on:click="open = ! open"></div>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($out);
});

it('handles colon-bound attributes', function () {
    $in = '<span :title="!busy ? a : b"></span>';
    $out = '<span :title="! busy ? a : b"></span>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($out);
});

it('is idempotent when a space already exists', function () {
    $in = '<button x-show="! recovery"></button>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('does not crash on a raw-text element whose attribute value is never closed', function () {
    // A missing closing quote leaves the parser at the end of the content; the
    // raw-text close lookup must not pass an out-of-range offset to stripos().
    $in = '<script src="never-closed';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('does not crash on a script tag with a ">" inside an attribute value', function () {
    $in = '<script data-cmp="a>b">var x = 1;</script>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('never touches the tailwind important modifier in class', function () {
    $in = '<p class="!mt-0 hover:!underline">x</p>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('leaves "!=" and "!==" comparisons untouched', function () {
    $in = '<input :class="ok != bad" x-data="{ n: a !== b }" />';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('leaves the "!!" double-negation untouched', function () {
    $in = '<i x-show="!!flag"></i>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('does not touch a "!" inside a javascript string literal', function () {
    $in = '<em x-on:click="alert(\'done!\')" x-text="\'!literal\'"></em>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('does not touch "!" inside a script body', function () {
    $in = '<script>if (!ready) { go(); }</script>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('does not touch "!" in plain html text', function () {
    $in = '<h2>Pinkary is now open-source! Join us.</h2>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('does not touch "!" inside a blade echo', function () {
    $in = '<div>{{ !$user->active }}</div>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($in);
});

it('processes a later attribute after an in-tag directive containing ">"', function () {
    $in = '<div @if ($count > 3) data-big @endif x-show="!hidden"></div>';
    $out = '<div @if ($count > 3) data-big @endif x-show="! hidden"></div>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($out);
});

it('handles multiple negations in a single value', function () {
    $in = '<div x-data="!a && !b"></div>';
    $out = '<div x-data="! a && ! b"></div>';

    expect((new NotOperatorSpacing)->postFormat($in))->toBe($out);
});
