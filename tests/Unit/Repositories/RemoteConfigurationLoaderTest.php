<?php

use App\Repositories\RemoteConfigurationLoader;
use Illuminate\Support\Facades\Http;

it('can load a remote configuration file', function () {
    Http::fakeSequence()
        ->push(file_get_contents(dirname(__DIR__, 2).'/Fixtures/rules/pint.json'));

    $loader = new RemoteConfigurationLoader();

    $config = $loader->load('https://example.com/config.json');

    expect($config)->toBeString()->toBeJson();
});

it('returns null if the remote configuration file is not found', function () {
    Http::fakeSequence()
        ->pushStatus(404);

    $loader = new RemoteConfigurationLoader();

    $config = $loader->load('https://example.com/config.json');

    expect($config)->toBeNull();
});
