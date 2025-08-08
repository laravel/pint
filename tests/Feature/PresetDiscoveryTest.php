<?php

use App\Services\PresetManifest;

it('can resolve preset paths', function () {
    $presetManifest = resolve(PresetManifest::class);

    expect($presetManifest->has('laravel'))->toBeTrue();
    expect($presetManifest->path('laravel'))->toContain('resources/presets/laravel.php');

    expect($presetManifest->has('nonexistent'))->toBeFalse();
    expect($presetManifest->path('nonexistent'))->toBeNull();
});

it('can list available presets', function () {
    $this->artisan('preset:list')
        ->expectsOutputToContain('laravel')
        ->expectsOutputToContain('per')
        ->expectsOutputToContain('psr12')
        ->expectsOutputToContain('symfony')
        ->expectsOutputToContain('empty')
        ->assertSuccessful();
});
