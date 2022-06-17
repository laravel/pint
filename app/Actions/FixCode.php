<?php

namespace App\Actions;

use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Runner\Runner;
use Symfony\Component\Console\Output\OutputInterface;

class FixCode
{
    /**
     * Creates a new Fixer instance.
     *
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  \Symfony\Component\EventDispatcher\EventDispatcher  $events
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \App\Output\ProgressOutput  $progress
     * @return void
     */
    public function __construct(
        protected $errors,
        protected $events,
        protected $output,
        protected $progress,
    ) {
        //
    }

    /**
     * Fixes the project resolved by the given configuration resolver.
     *
     * @param  \PhpCsFixer\Console\ConfigurationResolver  $resolver
     * @return array<int, string>
     */
    public function __invoke($resolver)
    {
        $this->progress->subscribe();

        $changes = with(new Runner(
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

        return tap($changes, fn () => $this->progress->unsubscribe());
    }
}
