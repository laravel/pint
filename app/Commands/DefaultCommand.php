<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DefaultCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Fix the coding style of the given path';

    /**
     * The configuration of the command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(
                [
                    new InputArgument('path', InputArgument::IS_ARRAY, 'The path to fix', [(string) getcwd()]),
                    new InputOption('config', '', InputOption::VALUE_REQUIRED, 'The configuration that should be used'),
                    new InputOption('no-config', '', InputOption::VALUE_NONE, 'Disable loading any configuration file'),
                    new InputOption('preset', '', InputOption::VALUE_REQUIRED, 'The preset that should be used'),
                    new InputOption('test', '', InputOption::VALUE_NONE, 'Test for code style errors without fixing them'),
                    new InputOption('bail', '', InputOption::VALUE_NONE, 'Test for code style errors without fixing them and stop on first error'),
                    new InputOption('repair', '', InputOption::VALUE_NONE, 'Fix code style errors but exit with status 1 if there were any changes made'),
                    new InputOption('dirty', '', InputOption::VALUE_NONE, 'Only fix files that have uncommitted changes'),
                    new InputOption('format', '', InputOption::VALUE_REQUIRED, 'The output format that should be used'),
                    new InputOption('cache-file', '', InputArgument::OPTIONAL, 'The path to the cache file'),
                ]
            );
    }

    /**
     * Execute the console command.
     *
     * @param  \App\Actions\FixCode  $fixCode
     * @param  \App\Actions\ElaborateSummary  $elaborateSummary
     * @return int
     */
    public function handle($fixCode, $elaborateSummary)
    {
        if (! ($_ENV['PINT_IGNORE_ENV'] ?? false) && version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $this->warn('PHP 8.4 is not currently supported by PHP CS Fixer and you may encounter unexpected behavior.');
            $this->warn('You can still force Pint to run by setting the environment variable:');
            $this->warn('PINT_IGNORE_ENV=1 vendor/bin/pint');

            return 1;
        }

        [$totalFiles, $changes] = $fixCode->execute();

        return $elaborateSummary->execute($totalFiles, $changes);
    }
}
