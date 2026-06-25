<?php

use App\Exceptions\PrettierException;
use App\Support\Prettier;
use Symfony\Component\Process\Process;

/**
 * A Prettier bound to a stub worker that never answers, with a one-second idle
 * timeout so the read loop's safety net can be exercised quickly.
 */
function silentPrettier(): Prettier
{
    return new class(base_path()) extends Prettier
    {
        public function workerPath(): string
        {
            return __DIR__.'/../Fixtures/prettier/silent-worker.js';
        }

        public function configPath(): ?string
        {
            return null;
        }

        protected function workerIdleTimeout(): int
        {
            return 1;
        }
    };
}

beforeEach(function () {
    $node = new Process(['node', '--version']);
    $node->run();

    if (! $node->isSuccessful()) {
        $this->markTestSkipped('Node is required to run the prettier worker.');
    }
});

it('throws naming the file instead of hanging when the worker never responds', function () {
    $prettier = silentPrettier();

    try {
        expect(fn () => $prettier->format('hangs.blade.php', '<div></div>'))
            ->toThrow(PrettierException::class, 'hangs.blade.php');
    } finally {
        $prettier->ensureTerminated();
    }
});

it('restarts the poisoned worker so a later request is not stuck behind it', function () {
    $prettier = silentPrettier();

    try {
        // The first request wedges and times out; because the worker is torn
        // down, the second request times out on a fresh one rather than hanging
        // forever behind the unanswered first.
        expect(fn () => $prettier->format('first.blade.php', '<div></div>'))
            ->toThrow(PrettierException::class, 'first.blade.php');

        expect(fn () => $prettier->format('second.blade.php', '<div></div>'))
            ->toThrow(PrettierException::class, 'second.blade.php');
    } finally {
        $prettier->ensureTerminated();
    }
});
