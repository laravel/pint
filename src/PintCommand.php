<?php

namespace Laravel\Pint;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'pint')]
class PintCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'pint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the coding style of the given path';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $binary = realpath(base_path('vendor/bin/pint'));

        if ($binary === false) {
            $this->components->error('The Pint binary could not be found at [vendor/bin/pint].');

            return static::FAILURE;
        }

        $command = [$binary];

        foreach ((array) $this->argument('path') as $path) {
            $command[] = $path;
        }

        if ($config = $this->option('config')) {
            $command[] = '--config='.$config;
        }

        if ($this->option('no-config')) {
            $command[] = '--no-config';
        }

        if ($preset = $this->option('preset')) {
            $command[] = '--preset='.$preset;
        }

        if ($this->option('test')) {
            $command[] = '--test';
        }

        if ($this->option('bail')) {
            $command[] = '--bail';
        }

        if ($this->option('repair')) {
            $command[] = '--repair';
        }

        if ($diff = $this->option('diff')) {
            $command[] = '--diff='.$diff;
        }

        if ($this->option('dirty')) {
            $command[] = '--dirty';
        }

        if ($format = $this->option('format')) {
            $command[] = '--format='.$format;
        }

        if ($outputToFile = $this->option('output-to-file')) {
            $command[] = '--output-to-file='.$outputToFile;
        }

        if ($outputFormat = $this->option('output-format')) {
            $command[] = '--output-format='.$outputFormat;
        }

        if ($cacheFile = $this->option('cache-file')) {
            $command[] = '--cache-file='.$cacheFile;
        }

        if ($this->option('parallel')) {
            $command[] = '--parallel';
        }

        if ($maxProcesses = $this->option('max-processes')) {
            $command[] = '--max-processes='.$maxProcesses;
        }

        $process = new Process($command, base_path());

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->getExitCode() ?? static::SUCCESS;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['path', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The path to fix', [(string) getcwd()]],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['config', '', InputOption::VALUE_REQUIRED, 'The configuration file that should be used'],
            ['no-config', '', InputOption::VALUE_NONE, 'Disable loading any configuration file'],
            ['preset', '', InputOption::VALUE_REQUIRED, 'The preset that should be used'],
            ['test', '', InputOption::VALUE_NONE, 'Test for code style errors without fixing them'],
            ['bail', '', InputOption::VALUE_NONE, 'Test without fixing and stop on the first error'],
            ['repair', '', InputOption::VALUE_NONE, 'Fix errors but exit with status 1 if there were changes'],
            ['diff', '', InputOption::VALUE_REQUIRED, 'Only fix files that have changed since the given branch', null, ['main', 'master', 'origin/main', 'origin/master']],
            ['dirty', '', InputOption::VALUE_NONE, 'Only fix files with uncommitted changes'],
            ['format', '', InputOption::VALUE_REQUIRED, 'The output format that should be used'],
            ['output-to-file', '', InputOption::VALUE_REQUIRED, 'Output the results to a file at this path'],
            ['output-format', '', InputOption::VALUE_REQUIRED, 'The format for file output'],
            ['cache-file', '', InputOption::VALUE_REQUIRED, 'The path to the cache file'],
            ['parallel', 'p', InputOption::VALUE_NONE, 'Run the linter in parallel (Experimental)'],
            ['max-processes', '', InputOption::VALUE_REQUIRED, 'The number of processes for parallel execution'],
        ];
    }
}
