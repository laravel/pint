<?php

namespace App\Support;

use App\Exceptions\PrettierException;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class Prettier
{
    /**
     * The configuration version, part of the worker's cache key.
     *
     * @var int
     */
    public const VERSION = 2;

    /**
     * The number of seconds the worker may stay silent before it is torn down.
     *
     * @var int
     */
    public const WORKER_IDLE_TIMEOUT = 30;

    /**
     * The prefix used to identify worker response lines.
     *
     * @var string
     */
    private const RESPONSE_PREFIX = '[PINT_PRETTIER_WORKER]';

    /**
     * The process instance, if any.
     */
    protected ?Process $process = null;

    /**
     * The input stream instance, if any.
     */
    protected ?InputStream $inputStream = null;

    /**
     * Create a new prettier instance.
     */
    public function __construct(protected string $projectRoot)
    {
        //
    }

    /**
     * The root directory of the node project.
     */
    public function projectRoot(): string
    {
        return $this->projectRoot;
    }

    /**
     * Formats the given file.
     *
     * @throws PrettierException
     */
    public function format(string $path, string $content): string
    {
        $result = $this->send([
            'type' => 'format',
            'path' => $path,
            'content' => $content,
        ]);

        if (! isset($result['formatted']) || ! is_string($result['formatted'])) {
            throw new PrettierException('Laravel Pint\'s Prettier worker did not return a valid response.');
        }

        return $result['formatted'];
    }

    /**
     * Format the given file and return its formatter ignore ranges.
     *
     * @return array{formatted: string, ranges: array<int, array{start: int, end: int, sourceStart: int, sourceEnd: int}>}
     *
     * @throws PrettierException
     */
    public function formatWithIgnoreRanges(string $path, string $content): array
    {
        $result = $this->send([
            'type' => 'format-with-ignore-ranges',
            'path' => $path,
            'content' => $content,
        ]);

        if (! isset($result['formatted'], $result['ranges']) || ! is_string($result['formatted']) || ! is_array($result['ranges'])) {
            throw new PrettierException('Laravel Pint\'s Prettier worker did not return a valid response.');
        }

        return $result;
    }

    /**
     * Return the formatter ignore ranges in the given file.
     *
     * @return array<int, array{start: int, end: int}>
     *
     * @throws PrettierException
     */
    public function ignoreRanges(string $path, string $content): array
    {
        if (stripos($content, 'format-ignore-start') === false
            && stripos($content, 'prettier-ignore-start') === false) {
            return [];
        }

        $result = $this->send([
            'type' => 'ranges',
            'path' => $path,
            'content' => $content,
        ]);

        if (! isset($result['ranges']) || ! is_array($result['ranges'])) {
            throw new PrettierException('Laravel Pint\'s Prettier worker did not return a valid response.');
        }

        return $result['ranges'];
    }

    /**
     * Send a request to the prettier worker.
     *
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     *
     * @throws PrettierException
     */
    private function send(array $request): array
    {
        $this->ensureStarted();

        $this->process->clearOutput();
        $this->process->clearErrorOutput();

        $this->inputStream->write(json_encode($request, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n");

        // Accumulate every chunk; the OS pipe may split the response across reads.
        $output = '';
        $error = '';
        $deadline = microtime(true) + $this->workerIdleTimeout();

        while (true) {
            if (($chunk = $this->process->getIncrementalOutput()) !== '') {
                $output .= $chunk;
                $deadline = microtime(true) + $this->workerIdleTimeout();
            }

            if ($this->responseFromOutput($output) !== null) {
                break;
            }

            if ($error .= $this->process->getIncrementalErrorOutput()) {
                break;
            }

            if (! $this->process->isRunning()) {
                $error = $this->process->getErrorOutput()
                    ?: 'Laravel Pint\'s Prettier worker terminated unexpectedly.';

                break;
            }

            // Alive but silent for too long; tear the worker down and report the file.
            if (microtime(true) >= $deadline) {
                $this->ensureTerminated();

                throw new PrettierException(sprintf(
                    'Laravel Pint\'s Prettier worker timed out while formatting [%s].',
                    $request['path'],
                ));
            }

            usleep(500);
        }

        $this->process->clearOutput();
        $this->process->clearErrorOutput();

        if ($error !== '') {
            throw new PrettierException($error);
        }

        $response = $this->responseFromOutput($output);

        if ($response === null) {
            throw new PrettierException('Laravel Pint\'s Prettier worker did not return a valid response.');
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            throw new PrettierException('Laravel Pint\'s Prettier worker did not return a valid response.');
        }

        return $decoded;
    }

    /**
     * Return the first complete worker response in the given output.
     */
    private function responseFromOutput(string $output): ?string
    {
        $lines = explode("\n", $output);
        array_pop($lines);

        foreach ($lines as $line) {
            if (str_starts_with($line, self::RESPONSE_PREFIX)) {
                return substr($line, strlen(self::RESPONSE_PREFIX));
            }
        }

        return null;
    }

    /**
     * The number of seconds the worker may stay silent before it is torn down.
     */
    protected function workerIdleTimeout(): int
    {
        return self::WORKER_IDLE_TIMEOUT;
    }

    /**
     * Ensures the process is started.
     */
    public function ensureStarted(): void
    {
        if ($this->process) {
            return;
        }

        $this->process = new Process(
            ['node', $this->workerPath(), $this->projectRoot, $this->configPath()],
            $this->projectRoot,
        );

        $this->process->setTty(false);

        $this->process->setInput(
            $this->inputStream = new InputStream,
        );

        $this->process->start();
    }

    /**
     * Ensures the process is terminated.
     */
    public function ensureTerminated(): void
    {
        if ($this->process) {
            $this->process->stop();

            $this->inputStream = null;
            $this->process = null;
        }
    }

    /**
     * The path to the bundled prettier worker on disk.
     */
    public function workerPath(): string
    {
        return $this->resourcePath('js/worker.cjs');
    }

    /**
     * The path to the bundled node script that probes installed package versions.
     */
    public function versionProbePath(): string
    {
        return $this->resourcePath('js/version-probe.cjs');
    }

    /**
     * The path to the bundled prettier configuration.
     */
    public function configPath(): string
    {
        return $this->resourcePath('prettier/prettierrc.json');
    }

    /**
     * Determine whether the bundled prettier resources ship with this distribution.
     */
    public function supported(): bool
    {
        return File::exists($this->resourcePath('js/worker.cjs'));
    }

    /**
     * Resolve the on-disk path to a bundled resource, relative to "resources".
     */
    protected function resourcePath(string $file): string
    {
        $phar = \Phar::running(false);

        // Inside a PHAR the package root is two levels up; else base_path().
        $root = $phar === '' ? base_path() : dirname($phar, 2);

        return $root.'/resources/'.$file;
    }
}
