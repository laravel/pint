<?php

use App\Enums\NodePackageManager;
use Tests\TestCase;

uses(TestCase::class)->beforeEach(function () {
    $this->root = sys_get_temp_dir().'/pint-package-manager-'.bin2hex(random_bytes(6));

    mkdir($this->root, 0777, true);
});

afterEach(function () {
    foreach (glob($this->root.'/*') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($this->root);
});

it('detects the package manager from the lock file', function (string $lockFile, NodePackageManager $expected) {
    touch($this->root.'/'.$lockFile);

    expect(NodePackageManager::detect($this->root))->toBe($expected);
})->with([
    'bun.lock' => ['bun.lock', NodePackageManager::Bun],
    'bun.lockb' => ['bun.lockb', NodePackageManager::Bun],
    'pnpm-lock.yaml' => ['pnpm-lock.yaml', NodePackageManager::Pnpm],
    'yarn.lock' => ['yarn.lock', NodePackageManager::Yarn],
    'package-lock.json' => ['package-lock.json', NodePackageManager::Npm],
]);

it('defaults to npm when there is no lock file', function () {
    expect(NodePackageManager::detect($this->root))->toBe(NodePackageManager::Npm);
});

it('prefers bun when several lock files are present', function () {
    touch($this->root.'/bun.lock');
    touch($this->root.'/yarn.lock');
    touch($this->root.'/package-lock.json');

    expect(NodePackageManager::detect($this->root))->toBe(NodePackageManager::Bun);
});

it('runs the bundled scripts with the matching runtime', function (NodePackageManager $manager, string $runtime) {
    expect($manager->runtimeBinary())->toBe($runtime);
})->with([
    'npm' => [NodePackageManager::Npm, 'node'],
    'yarn' => [NodePackageManager::Yarn, 'node'],
    'pnpm' => [NodePackageManager::Pnpm, 'node'],
    'bun' => [NodePackageManager::Bun, 'bun'],
]);

it('exposes the package manager binary', function (NodePackageManager $manager, string $binary) {
    expect($manager->binary())->toBe($binary);
})->with([
    'npm' => [NodePackageManager::Npm, 'npm'],
    'yarn' => [NodePackageManager::Yarn, 'yarn'],
    'pnpm' => [NodePackageManager::Pnpm, 'pnpm'],
    'bun' => [NodePackageManager::Bun, 'bun'],
]);

it('builds the development install command for each package manager', function (NodePackageManager $manager, array $command) {
    expect($manager->installCommand(['prettier@^3', 'prettier-plugin-blade']))->toBe($command);
})->with([
    'npm' => [NodePackageManager::Npm, ['npm', 'install', '-D', 'prettier@^3', 'prettier-plugin-blade']],
    'yarn' => [NodePackageManager::Yarn, ['yarn', 'add', '-D', 'prettier@^3', 'prettier-plugin-blade']],
    'pnpm' => [NodePackageManager::Pnpm, ['pnpm', 'add', '-D', 'prettier@^3', 'prettier-plugin-blade']],
    'bun' => [NodePackageManager::Bun, ['bun', 'add', '-d', 'prettier@^3', 'prettier-plugin-blade']],
]);
