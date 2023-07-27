<?php

use App\Repositories\ConfigurationLoaderResolver;
use App\Repositories\LocalConfigurationLoader;
use App\Repositories\RemoteConfigurationLoader;

it('can resolve a local configuration loader', function (?string $path) {
    $resolver = new ConfigurationLoaderResolver();

    $loader = $resolver->resolveFor($path);

    expect($loader)->toBeInstanceOf(LocalConfigurationLoader::class);
})->with([
    null,
    '',
    dirname(__DIR__, 2).'/Fixtures/rules/pint.json',
]);

it('can resolve a remote configuration loader', function (?string $path) {
    $resolver = new ConfigurationLoaderResolver();

    $loader = $resolver->resolveFor($path);

    expect($loader)->toBeInstanceOf(RemoteConfigurationLoader::class);
})->with([
    'https://example.com',
    'http://example.com',
]);
