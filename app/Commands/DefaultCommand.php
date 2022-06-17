<?php

namespace App\Commands;

use App\Actions\ElaborateSummary;
use App\Actions\FixCode;
use App\Factories\ConfigurationResolverFactory;
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
    protected $description = 'Fixes the project\'s coding style';

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
                    new InputArgument('path', InputArgument::OPTIONAL, 'The project\'s path.', (string) getcwd()),
                    new InputOption('preset', '', InputOption::VALUE_REQUIRED, 'The preset that should be used', 'psr12'),
                    new InputOption('risky', '', InputOption::VALUE_NONE, 'If risky fixers are allowed to be used.'),
                    new InputOption('test', '', InputOption::VALUE_NONE, 'If the project\'s coding style should be tested instead.'),
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
    public function handle(FixCode $fixCode, ElaborateSummary $elaborateSummary)
    {
        [$resolver, $totalFiles] = ConfigurationResolverFactory::fromIO($this->input, $this->output);

        $changes = with($resolver, $fixCode);

        return with([
            'totalFiles' => $totalFiles,
            'changes' => $changes,
        ], $elaborateSummary);
    }
}
