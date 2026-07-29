<?php

use App\Support\BladeIgnoreRanges;

it('protects and restores the given ranges', function () {
    $ranges = new BladeIgnoreRanges;
    $input = "Before\nIgnored\nAfter";
    $protected = $ranges->protect($input, [
        ['start' => 7, 'end' => 14],
    ], $input);

    expect($protected)
        ->toBe("Before\n__PINT_BLADE_IGNORE_0__\nAfter")
        ->and($ranges->restore($protected))->toBe($input);
});

it('restores source content when prettier adds text after an unclosed range', function () {
    $ranges = new BladeIgnoreRanges;
    $source = "Before\nIgnored\n";
    $formatted = "Before\nIgnored\n\n";
    $protected = $ranges->protect($formatted, [
        [
            'start' => 7,
            'end' => 17,
            'sourceStart' => 7,
            'sourceEnd' => 15,
        ],
    ], $source);

    expect($ranges->restore($protected))->toBe($source);
});

it('protects multiple ranges in their original order', function () {
    $ranges = new BladeIgnoreRanges;
    $input = 'A one B two C';
    $protected = $ranges->protect($input, [
        ['start' => 2, 'end' => 5],
        ['start' => 8, 'end' => 11],
    ], $input);

    expect($protected)->toBe('A __PINT_BLADE_IGNORE_0__ B __PINT_BLADE_IGNORE_1__ C');
    expect($ranges->restore($protected))->toBe($input);
});

it('selects a token that does not collide with the source', function () {
    $ranges = new BladeIgnoreRanges;
    $input = '__PINT_BLADE_IGNORE_0__ Ignored';

    expect($ranges->protect($input, [
        ['start' => 26, 'end' => 33],
    ], $input))->toEndWith('__PINT_BLADE_IGNORE_1__');
});

it('falls back to the original content when a token cannot be restored', function () {
    $ranges = new BladeIgnoreRanges;
    $input = 'Before Ignored After';

    $ranges->protect($input, [
        ['start' => 7, 'end' => 14],
    ], $input);

    expect($ranges->restore('The token is missing.'))->toBe($input);
});
