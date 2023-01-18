<?php

namespace App\Actions;

use App\Exceptions\IgnoringNoDirtyFiles;
use App\Factories\ConfigurationResolverFactory;
use PhpCsFixer\Runner\Runner;

class FixCode
{
    /**
     * Creates a new Fix Code instance.
     *
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  \Symfony\Component\EventDispatcher\EventDispatcher  $events
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \App\Output\ProgressOutput  $progress
     * @return void
     */
    public function __construct(
        protected $errors,
        protected $events,
        protected $input,
        protected $output,
        protected $progress,
    ) {
        //
    }

    /**
     * Fixes the project resolved by the current input and output.
     *
     * @return array{int, array<string, array{appliedFixers: array<int, string>, diff: string}>}
     */
    public function execute()
    {
        try {
            [$resolver, $totalFiles] = ConfigurationResolverFactory::fromIO($this->input, $this->output);
        } catch (IgnoringNoDirtyFiles $exception) {
            return [0, []];
        }

        if (is_null($this->input->getOption('format'))) {
            $this->progress->subscribe();
        }

        /** @var array<string, array{appliedFixers: array<int, string>, diff: string}> $changes */
        $changes = (new Runner(
            $resolver->getFinder(),
            $resolver->getFixers(),
            $resolver->getDiffer(),
            $this->events,
            $this->errors,
            $resolver->getLinter(),
            $resolver->isDryRun(),
            $resolver->getCacheManager(),
            $resolver->getDirectory(),
            $resolver->shouldStopOnViolation()
        ))->fix();

        return tap([$totalFiles, $changes], fn () => $this->progress->unsubscribe());
    }
}
