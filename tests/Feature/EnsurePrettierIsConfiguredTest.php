<?php

use App\Actions\EnsurePrettierIsConfigured;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;

/**
 * Build the action with a repository that reports the given rules.
 *
 * @param  array<string, mixed>  $rules
 */
function actionWithRules(array $rules): EnsurePrettierIsConfigured
{
    $configuration = Mockery::mock(ConfigurationJsonRepository::class);
    $configuration->shouldReceive('rules')->andReturn($rules);

    return new EnsurePrettierIsConfigured(new Prettier(base_path()), $configuration);
}

/**
 * Invoke the otherwise protected "needsPrettier" check on the action.
 */
function needsPrettier(EnsurePrettierIsConfigured $action): bool
{
    return (fn () => $this->needsPrettier())->call($action);
}

it('does not need prettier when no prettier-backed rule is enabled', function () {
    expect(needsPrettier(actionWithRules([])))->toBeFalse();
});

it('does not need prettier when the blade rule is explicitly disabled', function () {
    expect(needsPrettier(actionWithRules(['Pint/laravel_blade' => false])))->toBeFalse();
});

it('needs prettier when the blade rule is enabled', function () {
    expect(needsPrettier(actionWithRules(['Pint/laravel_blade' => true])))->toBeTrue();
});

it('requires prettier and its blade plugins as dependencies', function () {
    $packages = actionWithRules(['Pint/laravel_blade' => true])->requiredPackages();

    expect($packages)
        ->toContain('prettier')
        ->toContain('prettier-plugin-blade')
        ->toContain('prettier-plugin-tailwindcss');
});
