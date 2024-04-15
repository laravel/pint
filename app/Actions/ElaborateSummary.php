<?php

namespace App\Actions;

use Illuminate\Console\Command;
use PhpCsFixer\Console\Report\FixReport;
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
     * @param  array<string, array{appliedFixers: array<int, string>, diff: string}>  $changes
     * @return int
     */
    public function execute($totalFiles, $changes)
    {
        $summary = new FixReport\ReportSummary(
            $changes,
            $totalFiles,
            0,
            0,
            $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE,
            $this->input->getOption('test') || $this->input->getOption('bail'),
            $this->output->isDecorated()
        );

        if ($this->input->getOption('format')) {
            $this->displayUsingFormatter($summary, $totalFiles);
        } else {
            $this->summaryOutput->handle($summary, $totalFiles);
        }

        $failure = ($summary->isDryRun() && count($changes) > 0)
            || count($this->errors->getInvalidErrors()) > 0
            || count($this->errors->getExceptionErrors()) > 0
            || count($this->errors->getLintErrors()) > 0;

        return $failure ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Formats the given summary using the "selected" formatter.
     *
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $summary
     * @param  int  $totalFiles
     * @return void
     */
    protected function displayUsingFormatter($summary, $totalFiles)
    {
        $reporter = match ($format = $this->input->getOption('format')) {
            'checkstyle' => new FixReport\CheckstyleReporter(),
            'gitlab' => new FixReport\GitlabReporter(),
            'json' => new FixReport\JsonReporter(),
            'junit' => new FixReport\JunitReporter(),
            'txt' => new FixReport\TextReporter(),
            'xml' => new FixReport\XmlReporter(),
            default => abort(1, sprintf('Format [%s] is not supported.', $format)),
        };

        $this->output->write($reporter->generate($summary));
    }
}
