<?php

use App\BladeFormatter;
use App\Fixers\LaravelBlade\Fixer;
use App\Support\Prettier;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;

beforeEach(function () {
    $this->fixer = new Fixer(new BladeFormatter(new Prettier(getcwd())));
});

it('sorts tailwind classes by default', function () {
    expect($this->fixer->prettierDependencies())->toHaveKey('prettier-plugin-tailwindcss')
        ->and($this->fixer->prettierOptions())->toBe([]);
});

it('sorts tailwind classes when enabled', function () {
    $this->fixer->configure(['sort_tailwind_classes' => true]);

    expect($this->fixer->prettierDependencies())->toHaveKey('prettier-plugin-tailwindcss')
        ->and($this->fixer->prettierOptions())->toBe([]);
});

it('does not load nor require the tailwind plugin when sorting is disabled', function () {
    $this->fixer->configure(['sort_tailwind_classes' => false]);

    expect($this->fixer->prettierDependencies())->toBe([
        'prettier' => '^3.9.6',
        'prettier-plugin-blade' => '^3.3.3',
    ])->and($this->fixer->prettierOptions())->toBe([
        'plugins' => ['prettier-plugin-blade'],
    ]);
});

it('rejects an invalid sort_tailwind_classes option', function () {
    $this->fixer->configure(['sort_tailwind_classes' => 'no']);
})->throws(InvalidFixerConfigurationException::class);
