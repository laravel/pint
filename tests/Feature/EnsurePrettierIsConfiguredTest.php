<?php

use App\Actions\EnsurePrettierIsConfigured;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;
use LaravelZero\Framework\Exceptions\ConsoleException;

/**
 * Invoke the otherwise protected configuration check on the action.
 */
function ensureConfigured(Prettier $prettier): void
{
    $action = new EnsurePrettierIsConfigured($prettier, new ConfigurationJsonRepository(null, null));

    (fn () => $this->ensurePrettierNodeDependencyIsConfigured())->call($action);
}

it('passes when the project has no custom prettier config', function () {
    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('hasCustomPrettierConfig')->andReturn(false);

    ensureConfigured($prettier);
})->throwsNoExceptions();

it('passes when the custom config declares every default option with the expected value', function () {
    $defaults = (new Prettier(base_path()))->defaultOptions();

    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('hasCustomPrettierConfig')->andReturn(true);
    $prettier->shouldReceive('hasPlugins')->andReturn(true);
    $prettier->shouldReceive('defaultOptions')->andReturn($defaults);
    $prettier->shouldReceive('resolveCustomOptions')->andReturn($defaults);

    ensureConfigured($prettier);
})->throwsNoExceptions();

it('aborts naming an option the custom config is missing', function () {
    $defaults = (new Prettier(base_path()))->defaultOptions();
    $resolved = $defaults;
    unset($resolved['bladeEchoSpacing']);

    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('hasCustomPrettierConfig')->andReturn(true);
    $prettier->shouldReceive('hasPlugins')->andReturn(true);
    $prettier->shouldReceive('defaultOptions')->andReturn($defaults);
    $prettier->shouldReceive('resolveCustomOptions')->andReturn($resolved);

    ensureConfigured($prettier);
})->throws(ConsoleException::class, 'bladeEchoSpacing');

it('aborts naming an option the custom config sets to a different value', function () {
    $defaults = (new Prettier(base_path()))->defaultOptions();
    $resolved = $defaults;
    $resolved['printWidth'] = 80;

    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('hasCustomPrettierConfig')->andReturn(true);
    $prettier->shouldReceive('hasPlugins')->andReturn(true);
    $prettier->shouldReceive('defaultOptions')->andReturn($defaults);
    $prettier->shouldReceive('resolveCustomOptions')->andReturn($resolved);

    ensureConfigured($prettier);
})->throws(ConsoleException::class, 'printWidth = 120');

it('aborts when the custom config is missing a required plugin', function () {
    $defaults = (new Prettier(base_path()))->defaultOptions();

    $prettier = Mockery::mock(Prettier::class);
    $prettier->shouldReceive('hasCustomPrettierConfig')->andReturn(true);
    $prettier->shouldReceive('hasPlugins')->andReturn(false);
    $prettier->shouldReceive('defaultOptions')->andReturn($defaults);
    $prettier->shouldReceive('resolveCustomOptions')->andReturn($defaults);

    ensureConfigured($prettier);
})->throws(ConsoleException::class, 'prettier');
