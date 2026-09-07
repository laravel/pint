<?php

use App\Support\Prettier;
use Tests\TestCase;

uses(TestCase::class)->beforeEach(function () {
    $this->root = sys_get_temp_dir().'/pint-prettier-'.bin2hex(random_bytes(6));

    mkdir($this->root, 0777, true);
});

afterEach(function () {
    foreach (glob($this->root.'/*') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($this->root);
});

it('runs the bundled scripts with node by default', function () {
    expect((new Prettier($this->root))->runtimeBinary())->toBe('node');
});

it('runs the bundled scripts with node for npm, yarn and pnpm projects', function (string $lockFile) {
    touch($this->root.'/'.$lockFile);

    expect((new Prettier($this->root))->runtimeBinary())->toBe('node');
})->with(['package-lock.json', 'yarn.lock', 'pnpm-lock.yaml']);

it('runs the bundled scripts with bun for bun projects', function (string $lockFile) {
    touch($this->root.'/'.$lockFile);

    expect((new Prettier($this->root))->runtimeBinary())->toBe('bun');
})->with(['bun.lock', 'bun.lockb']);

it('resolves the runtime from the project root it was given', function () {
    touch($this->root.'/bun.lock');

    expect((new Prettier($this->root))->runtimeBinary())->toBe('bun')
        ->and((new Prettier(sys_get_temp_dir()))->runtimeBinary())->toBe('node');
});
