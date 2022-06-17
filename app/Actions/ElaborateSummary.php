<?php

namespace App\Actions;

use App\Output\SummaryOutput;
use Illuminate\Console\Command;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use Symfony\Component\Console\Output\OutputInterface;

class ElaborateSummary
{
    /**
     * Creates a new Fixer instance.
     *
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \App\Output\SummaryOutput  $summaryOutput
     * @return void
     */
    public function __construct(
        protected $errors,
        protected $input,
        protected $output,
        protected $summaryOutput,
    ) {
        //
    }

    /**
     * Elaborates the summary of the given changes.
     *
     * @param  array{'totalFiles': int, 'changes': array<int, string>}  $context
     * @return int
     */
    public function __invoke($context)
    {
        $summary = new ReportSummary(
            $context['changes'],
            0,
            0,
            OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity(),
            $this->input->getOption('test'),
            $this->output->isDecorated()
        );

        $changes = tap($summary, fn () => $this->summaryOutput->handle($summary, $context['totalFiles']))->getChanged();

        $failure = ($summary->isDryRun() && count($changes) > 0)
            || count($this->errors->getInvalidErrors()) > 0
            || count($this->errors->getExceptionErrors()) > 0
            || count($this->errors->getLintErrors()) > 0;

        return $failure ? Command::FAILURE : Command::SUCCESS;
    }
}
