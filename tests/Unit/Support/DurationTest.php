<?php

use App\Support\Duration;

it('formats sub-minute durations in seconds', function (float $seconds, string $expected) {
    expect(Duration::format($seconds))->toBe($expected);
})->with([
    [0.0, '0.00s'],
    [0.005, '0.01s'],
    [1.234, '1.23s'],
    [59.999, '60.00s'],
]);

it('formats durations of a minute or more in minutes and seconds', function (float $seconds, string $expected) {
    expect(Duration::format($seconds))->toBe($expected);
})->with([
    [60.0, '1m 00s'],
    [65.4, '1m 05s'],
    [125.9, '2m 05s'],
    [3600.0, '60m 00s'],
]);
