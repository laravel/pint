<?php

namespace App;

use App\Exceptions\PrettierException;
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
     * @param  string  $file
     */
    public function format($file): string
    {
        $this->sandbox->ensureInitialized();

        $this->ensureStarted();

        $this->inputStream->write($file);

        while (true) {
            $formatted = $this->process->getOutput();
            $error = $this->process->getErrorOutput();

            if ($formatted || $error) {
                break;
            }
        }

        $this->process->clearOutput();
        $this->process->clearErrorOutput();

        if ($error) {
            throw new PrettierException($error);
        }

        return $formatted;
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
     * Terminates the process.
     *
     * @return void
     */
    public function terminate()
    {
        if ($this->process) {
            $this->process->stop();
        }
    }
}
