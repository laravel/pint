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

/**
 * Invoke the otherwise protected "unsatisfied" decision logic on the action.
 *
 * @param  array<string, array{resolved: bool, version: string|null}>  $probes
 * @param  array<string, string>  $required
 * @return array<int, array{package: string, installed: string, constraint: string}>
 */
function unsatisfied(EnsurePrettierIsConfigured $action, array $probes, array $required): array
{
    return (fn () => $this->unsatisfied($probes, $required))->call($action);
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

it('requires prettier and its blade plugins as dependencies with version constraints', function () {
    $packages = actionWithRules(['Pint/laravel_blade' => true])->requiredPackages();

    expect($packages)
        ->toHaveKey('prettier')
        ->toHaveKey('prettier-plugin-blade')
        ->toHaveKey('prettier-plugin-tailwindcss')
        ->and($packages['prettier'])->toStartWith('^')
        ->and($packages['prettier-plugin-blade'])->toStartWith('^')
        ->and($packages['prettier-plugin-tailwindcss'])->toStartWith('^');
});

it('reports a resolved package whose version is below the required constraint as unsatisfied', function () {
    $outdated = unsatisfied(
        actionWithRules(['Pint/laravel_blade' => true]),
        ['prettier' => ['resolved' => true, 'version' => '3.0.0']],
        ['prettier' => '^3.8.4'],
    );

    expect($outdated)->toBe([
        ['package' => 'prettier', 'installed' => '3.0.0', 'constraint' => '^3.8.4'],
    ]);
});

it('reports a resolved package whose major is too new as unsatisfied', function () {
    $outdated = unsatisfied(
        actionWithRules(['Pint/laravel_blade' => true]),
        ['prettier' => ['resolved' => true, 'version' => '4.0.0']],
        ['prettier' => '^3.8.4'],
    );

    expect($outdated)->toBe([
        ['package' => 'prettier', 'installed' => '4.0.0', 'constraint' => '^3.8.4'],
    ]);
});

it('does not report a satisfied package as unsatisfied', function () {
    $outdated = unsatisfied(
        actionWithRules(['Pint/laravel_blade' => true]),
        ['prettier' => ['resolved' => true, 'version' => '3.8.4']],
        ['prettier' => '^3.8.4'],
    );

    expect($outdated)->toBe([]);
});

it('skips missing packages when determining unsatisfied versions', function () {
    $outdated = unsatisfied(
        actionWithRules(['Pint/laravel_blade' => true]),
        ['prettier' => ['resolved' => false, 'version' => null]],
        ['prettier' => '^3.8.4'],
    );

    expect($outdated)->toBe([]);
});

it('skips resolved packages whose version could not be determined', function () {
    $outdated = unsatisfied(
        actionWithRules(['Pint/laravel_blade' => true]),
        ['prettier' => ['resolved' => true, 'version' => null]],
        ['prettier' => '^3.8.4'],
    );

    expect($outdated)->toBe([]);
});
