<?php

namespace App\Actions;

use Illuminate\Console\Command;
use PhpCsFixer\Console\Report\FixReport\CheckstyleReporter;
use PhpCsFixer\Console\Report\FixReport\GitlabReporter;
use PhpCsFixer\Console\Report\FixReport\JsonReporter;
use PhpCsFixer\Console\Report\FixReport\JunitReporter;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Console\Report\FixReport\TextReporter;
use PhpCsFixer\Console\Report\FixReport\XmlReporter;
use Symfony\Component\Console\Exception\InvalidOptionException;
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
     * @param  ReportSummary  $summary
     * @return string
     *
     * @throws InvalidOptionException
     */
    private function report($summary)
    {
        $format = $this->input->getOption('format');
        $reporter = match ($format) {
            'checkstyle' => new CheckstyleReporter(),
            'gitlab' => new GitlabReporter(),
            'json' => new JsonReporter(),
            'junit' => new JunitReporter(),
            'txt' => new TextReporter(),
            'xml' => new XmlReporter(),
            default => throw new InvalidOptionException(sprintf('Format "%s" is not supported.', $format))
        };

        return $reporter->generate($summary);
    }
}
