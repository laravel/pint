<?php

use App\Actions\EnsurePrettierIsConfigured;
use App\BladeFormatter;
use App\Fixers\LaravelBlade\Fixer;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;

beforeEach(function () {
    $prettier = new Prettier(getcwd());

    $this->action = new EnsurePrettierIsConfigured($prettier, new ConfigurationJsonRepository(null, null));
    $this->fixer = new Fixer(new BladeFormatter($prettier));

    $this->probes = [
        'prettier' => ['resolved' => true, 'version' => '3.8.4'],
        'prettier-plugin-blade' => ['resolved' => true, 'version' => '3.2.2'],
        'prettier-plugin-tailwindcss' => ['resolved' => true, 'version' => '0.8.0'],
    ];
});

it('computes a stable fingerprint from the probed package versions', function () {
    $first = $this->action->fingerprint($this->fixer, $this->probes);
    $second = $this->action->fingerprint($this->fixer, array_reverse($this->probes, true));

    expect($first)->toMatch('/^[a-f0-9]{32}$/')
        ->and($second)->toBe($first);
});

it('changes the fingerprint when a prettier package version changes', function () {
    $before = $this->action->fingerprint($this->fixer, $this->probes);

    $this->probes['prettier-plugin-blade']['version'] = '3.3.0';

    expect($this->action->fingerprint($this->fixer, $this->probes))->not->toBe($before);
});

it('changes the fingerprint when the pint version changes', function () {
    config(['app.version' => '1.0.0']);
    $before = $this->action->fingerprint($this->fixer, $this->probes);

    config(['app.version' => '1.0.1']);

    expect($this->action->fingerprint($this->fixer, $this->probes))->not->toBe($before);
});

it('exposes no cache fingerprints before prettier is configured', function () {
    expect($this->action->cacheFingerprints())->toBe([]);
});

it('changes the fingerprint when tailwind class sorting is disabled', function () {
    $before = $this->action->fingerprint($this->fixer, $this->probes);

    $this->fixer->configure(['sort_tailwind_classes' => false]);

    expect($this->action->fingerprint($this->fixer, $this->probes))->not->toBe($before);
});

it('requires the tailwind plugin only when tailwind class sorting is enabled', function (string $configuration, bool $requiresTailwind) {
    $action = new EnsurePrettierIsConfigured(
        new Prettier(getcwd()),
        new ConfigurationJsonRepository(dirname(__DIR__).'/Fixtures/rules/'.$configuration, null),
    );

    expect(array_key_exists('prettier-plugin-tailwindcss', $action->requiredPackages()))->toBe($requiresTailwind)
        ->and($action->requiredPackages())->toHaveKeys(['prettier', 'prettier-plugin-blade']);
})->with([
    'enabled' => ['blade-enabled.json', true],
    'sorting disabled' => ['blade-configured.json', false],
]);
