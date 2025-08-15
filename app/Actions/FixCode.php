<?php

namespace App\Actions;

use App\Factories\ConfigurationResolverFactory;
use LaravelZero\Framework\Exceptions\ConsoleException;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\Runner\Parallel\ParallelConfig;
use PhpCsFixer\Runner\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

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
        } catch (ConsoleException $exception) {
            return [$exception->getCode(), []];
        }

        if (is_null($this->input->getOption('format'))) {
            $this->progress->subscribe();
        }

        $method = $this->input->getOption('parallel') ? 'fixParallel' : 'fixSequential';

        /** @var array<string, array{appliedFixers: array<int, string>, diff: string}> $changes */
        $changes = (fn () => $this->{$method}())->call(new Runner(
            $resolver->getFinder(),
            $resolver->getFixers(),
            $resolver->getDiffer(),
            $this->events,
            $this->errors,
            $resolver->getLinter(),
            $resolver->isDryRun(),
            $resolver->getCacheManager(),
            $resolver->getDirectory(),
            $resolver->shouldStopOnViolation(),
            $this->getParallelConfig($resolver),
            $this->getInput($resolver),
        ));

        return tap([$totalFiles, $changes], fn () => $this->progress->unsubscribe());
    }

    /**
     * Get the ParallelConfig for the number of cores.
     */
    private function getParallelConfig(ConfigurationResolver $resolver): ParallelConfig
    {
        $maxProcesses = intval($this->input->getOption('max-processes') ?? 0);

        if (! $this->input->getOption('parallel') || $maxProcesses < 1) {
            return $resolver->getParallelConfig();
        }

        $parallelConfig = $resolver->getParallelConfig();

        return new ParallelConfig(
            $maxProcesses,
            $parallelConfig->getFilesPerProcess(),
            $parallelConfig->getProcessTimeout()
        );
    }

    /**
     * Get the input for the PHP CS Fixer Runner.
     */
    private function getInput(ConfigurationResolver $resolver): InputInterface
    {
        // @phpstan-ignore-next-line
        $definition = (fn () => $this->definition)->call($this->input);

        $definition->addOptions([
            new InputOption('stop-on-violation', null, InputOption::VALUE_REQUIRED, ''),
            new InputOption('allow-risky', null, InputOption::VALUE_REQUIRED, ''),
            new InputOption('rules', null, InputOption::VALUE_REQUIRED, ''),
            new InputOption('using-cache', null, InputOption::VALUE_REQUIRED, ''),
        ]);

        $this->input->setOption('stop-on-violation', $resolver->shouldStopOnViolation());
        $this->input->setOption('allow-risky', $resolver->getRiskyAllowed() ? 'yes' : 'no');
        $this->input->setOption('rules', json_encode($resolver->getRules()));
        $this->input->setOption('using-cache', $resolver->getUsingCache() ? 'yes' : 'no');

        return $this->input;
    }
}
