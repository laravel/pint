<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use Symfony\Component\Console\Output\OutputInterface;

class ElaborateSummary
{
    /**
     * Creates a new Elaborate Summary instance.
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
     * @param  int  $totalFiles
     * @param  array<int, string>  $changes
     * @return int
     */
    public function execute($totalFiles, $changes)
    {
        $summary = new ReportSummary(
            $changes,
            0,
            0,
            OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity(),
            $this->input->getOption('test'),
            $this->output->isDecorated()
        );

        $this->format($summary);

        if (
            $this->input->getOption('format') === 'txt'
            || $this->input->getOption('report') !== null
        ) {
            tap($summary, fn () => $this->summaryOutput->handle($summary, $totalFiles))->getChanged();
        }

        $failure = ($summary->isDryRun() && count($changes) > 0)
            || count($this->errors->getInvalidErrors()) > 0
            || count($this->errors->getExceptionErrors()) > 0
            || count($this->errors->getLintErrors()) > 0;

        return $failure ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param  ReportSummary  $summary
     * @return void
     */
    private function format($summary)
    {
        if ($this->input->getOption('format') === 'txt') {
            return;
        }

        $report = $this->report($summary);
        if ($this->input->getOption('report') === null) {
            $this->output->writeln($report);
        } else {
            file_put_contents($this->input->getOption('report'), stripcslashes($report), LOCK_EX);
        }
    }

    /**
     * @param ReportSummary $summary
     * @return string
     */
    private function report($summary)
    {
        $reporters = App::tagged('reporters');
        $format = $this->input->getOption('format');

        foreach ($reporters as $reporter) {
            if ($format === $reporter->getFormat()) {
                return $reporter->generate($summary);
            }
        }
    }
}
