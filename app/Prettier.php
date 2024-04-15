<?php

namespace App;

use App\Exceptions\PrettierException;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class Prettier
{
    /**
     * The node sandbox instance.
     *
     * @var \App\NodeSandbox
     */
    protected $sandbox;

    /**
     * The process instance, if any.
     *
     * @var Process|null
     */
    protected $process;

    /**
     * The input stream instance, if any.
     *
     * @var \Symfony\Component\Process\InputStream|null
     */
    protected $inputStream;

    /**
     * The node sandbox instance.
     *
     * @param  \App\NodeSandbox  $sandbox
     * @return void
     */
    public function __construct($sandbox)
    {
        $this->sandbox = $sandbox;
    }

    /**
     * Formats the given file.
     *
     * @param  string  $path
     * @param  string  $content
     * @return string
     *
     * @throws \App\Exceptions\PrettierException
     */
    public function format($path, $content)
    {
        $this->sandbox->ensureInitialized();

        $this->ensureStarted();

        $this->inputStream->write(json_encode([
            'path' => $path,
            'content' => $content,
        ]));

        $this->process->clearOutput();
        $this->process->clearErrorOutput();

        $error = '';

        while (true) {
            $formatted = $this->process->getIncrementalOutput();

            if (Str::endsWith($formatted, '[PINT_BLADE_PRETTIER_WORKER_END]')) {
                break;
            }

            if ($error = $this->process->getIncrementalErrorOutput()) {
                break;
            }
        }

        $this->process->clearOutput();
        $this->process->clearErrorOutput();

        if ($error !== '') {
            throw new PrettierException($error);
        }

        foreach ([
            '[PINT_BLADE_PRETTIER_WORKER_START]',
            '[PINT_BLADE_PRETTIER_WORKER_END]',
        ] as $delimiter) {
            if (! Str::contains($formatted, $delimiter)) {
                throw new PrettierException('Laravel Pint\'s Prettier worker did not return a valid response.');
            }
        }

        return Str::of($formatted)
            ->after('[PINT_BLADE_PRETTIER_WORKER_START]')
            ->before('[PINT_BLADE_PRETTIER_WORKER_END]')
            ->value();
    }

    /**
     * Ensures the process is started.
     *
     * @return void
     */
    public function ensureStarted()
    {
        if ($this->process) {
            return;
        }

        $this->process = new Process(
            ['node', $this->sandbox->path().'/prettier-worker.js'],
            $this->sandbox->path(),
        );

        $this->process->setTty(false);

        $this->process->setInput(
            $this->inputStream = new InputStream(),
        );

        $this->process->start();
    }

    /**
     * Ensures the process is terminated.
     *
     * @return void
     */
    public function ensureTerminated()
    {
        if ($this->process) {
            $this->process->stop();

            $this->inputStream = null;
            $this->process = null;
        }
    }
}
