<?php

namespace App\Actions;

use App\Factories\ConfigurationResolverFactory;
use PhpCsFixer\Runner\Runner;

class FixCode
{
    /**
     * Creates a new Fixer instance.
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
     * Fixes the project resolved by the given configuration resolver.
     *
     * @return array{int, array<int, string>}
     */
    public function execute()
    {
        [$resolver, $totalFiles] = ConfigurationResolverFactory::fromIO($this->input, $this->output);

        $this->progress->subscribe();

        /** @var array<int, string> $changes */
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

        return tap([$totalFiles, $changes], fn () => $this->progress->unsubscribe());
    }
}
