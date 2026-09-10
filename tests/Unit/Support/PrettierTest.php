<?php

use App\Exceptions\PrettierException;
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

function prettierWithFakeWorker(): Prettier
{
    return new class(dirname(__DIR__, 3)) extends Prettier
    {
        public function workerPath(): string
        {
            return dirname(__DIR__, 2).'/Fixtures/prettier-worker.cjs';
        }

        public function configPath(): string
        {
            return __FILE__;
        }
    };
}

it('does not start the worker for content without ignore range markers', function () {
    $prettier = new Prettier('/missing-project');

    expect($prettier->ignoreRanges('view.blade.php', '<div>Content</div>'))->toBe([]);
});

it('rejects invalid UTF-8 before calculating ignore range offsets', function () {
    $prettier = new Prettier('/missing-project');
    $content = "<div>\xFF</div>\n{{-- format-ignore-start --}}\nraw\n{{-- format-ignore-end --}}\n";

    $prettier->ignoreRanges('view.blade.php', $content);
})->throws(PrettierException::class, 'Laravel Pint cannot preserve Blade formatter ignore ranges in files containing invalid UTF-8.');

it('reads a worker response after stdout noise without a trailing newline', function () {
    $prettier = prettierWithFakeWorker();

    try {
        expect($prettier->format('view.blade.php', '<div>Content</div>'))->toBe('<div>Content</div>');
    } finally {
        $prettier->ensureTerminated();
    }
});

it('preserves response-prefix text in formatted content', function () {
    $prettier = prettierWithFakeWorker();
    $content = "[PINT_PRETTIER_WORKER]\n";

    try {
        expect($prettier->format('view.blade.php', $content))->toBe($content);
    } finally {
        $prettier->ensureTerminated();
    }
});
