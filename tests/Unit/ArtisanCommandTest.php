<?php

use Illuminate\Foundation\Application;
use Laravel\Pint\PintCommand;
use Laravel\Pint\PintServiceProvider;

it('has the correct command name', function () {
    expect((new PintCommand)->getName())->toBe('pint');
});

it('has the correct description', function () {
    expect((new PintCommand)->getDescription())->toBe('Fix the coding style of the given path');
});

it('defines all pint options', function () {
    $options = collect((new PintCommand)->getDefinition()->getOptions())
        ->keys()
        ->sort()
        ->values()
        ->all();

    expect($options)->toBe([
        'bail',
        'cache-file',
        'config',
        'diff',
        'dirty',
        'format',
        'max-processes',
        'no-config',
        'output-format',
        'output-to-file',
        'parallel',
        'preset',
        'repair',
        'test',
    ]);
});

it('boots the service provider without errors when not in console', function () {
    $app = Mockery::mock(Application::class);
    $app->shouldReceive('runningInConsole')->once()->andReturn(false);

    $provider = new PintServiceProvider($app);

    expect(fn () => $provider->boot())->not->toThrow(Throwable::class);
});
