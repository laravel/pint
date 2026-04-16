<?php

namespace App\Actions;

use App\Factories\ConfigurationResolverFactory;
use App\Output\AgentReporter;
use App\Output\SummaryOutput;
use Illuminate\Console\Command;
use PhpCsFixer\Console\Report\FixReport;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Error\Error;
use PhpCsFixer\Error\ErrorsManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElaborateSummary
{
    /**
     * Creates a new Elaborate Summary instance.
     *
     * @param  ErrorsManager  $errors
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @param  SummaryOutput  $summaryOutput
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
        $summary = new ReportSummary(
            $changes,
            $totalFiles,
            0,
            0,
            $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE,
            $this->input->getOption('test') || $this->input->getOption('bail'),
            $this->output->isDecorated()
        );

        $format = $this->input->getOption('format');

        if (ConfigurationResolverFactory::runningInAgent()) {
            $this->displayUsingFormatter($summary, 'agent');
        } elseif ($format) {
            $this->displayUsingFormatter($summary, $format);
        } else {
            $this->summaryOutput->handle($summary, $totalFiles);
        }

        if (($file = $this->input->getOption('output-to-file')) && (($outputFormat = $this->input->getOption('output-format')) || $format)) {
            $this->displayUsingFormatter($summary, $outputFormat ?: $format, $file);
        }

        if ($this->shouldDisplayErrorsUsingErrorOutput($format, $this->input->getOption('output-format'))) {
            $this->displayErrorsUsingErrorOutput();
        }

        $failure = (($summary->isDryRun() || $this->input->getOption('repair')) && count($changes) > 0)
            || count($this->errors->getInvalidErrors()) > 0
            || count($this->errors->getExceptionErrors()) > 0
            || count($this->errors->getLintErrors()) > 0;

        return $failure ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Formats the given summary using the "selected" formatter.
     *
     * @param  ReportSummary  $summary
     * @return void
     *
     * @throws \JsonException
     */
    protected function displayUsingFormatter($summary, ?string $format = null, ?string $outputPath = null)
    {
        $reporter = match ($format) {
            'agent' => new AgentReporter($this->errors),
            'checkstyle' => new FixReport\CheckstyleReporter,
            'gitlab' => new FixReport\GitlabReporter,
            'json' => new FixReport\JsonReporter,
            'junit' => new FixReport\JunitReporter,
            'txt' => new FixReport\TextReporter,
            'xml' => new FixReport\XmlReporter,
            default => abort(1, sprintf('Format [%s] is not supported.', $format)),
        };

        if ($outputPath) {
            file_put_contents($outputPath, $reporter->generate($summary));

            return;
        }

        $this->output->write($reporter->generate($summary));
    }

    /**
     * Determine if formatter-based output should be accompanied by stderr errors.
     *
     * @param  string|null  $format
     * @param  string|null  $outputFormat
     * @return bool
     */
    protected function shouldDisplayErrorsUsingErrorOutput($format, $outputFormat)
    {
        $formats = array_filter([$format, $outputFormat]);

        return count(array_intersect($formats, [
            'checkstyle',
            'gitlab',
            'json',
            'junit',
            'xml',
        ])) > 0;
    }

    /**
     * Display linting and fixing errors on stderr when available.
     *
     * This preserves machine-readable stdout output while still surfacing
     * the reason the command failed.
     *
     * @return void
     */
    protected function displayErrorsUsingErrorOutput()
    {
        $errorOutput = fopen('php://stderr', 'w');

        if ($errorOutput === false) {
            return;
        }

        if (count($this->errors->getInvalidErrors()) > 0) {
            $this->writeErrors($errorOutput, 'linting before fixing', $this->errors->getInvalidErrors());
        }

        if (count($this->errors->getExceptionErrors()) > 0) {
            $this->writeErrors($errorOutput, 'fixing', $this->errors->getExceptionErrors());
        }

        if (count($this->errors->getLintErrors()) > 0) {
            $this->writeErrors($errorOutput, 'linting after fixing', $this->errors->getLintErrors());
        }

        fclose($errorOutput);
    }

    /**
     * Write the given errors to stderr in a concise human-readable format.
     *
     * @param  resource  $output
     * @param  array<int, Error>  $errors
     * @return void
     */
    protected function writeErrors($output, string $process, $errors)
    {
        fwrite($output, PHP_EOL);
        fwrite($output, sprintf(
            'Files that were not fixed due to errors reported during %s:',
            $process,
        ).PHP_EOL);

        foreach ($errors as $index => $error) {
            fwrite($output, sprintf(
                '  %d) %s',
                $index + 1,
                $error->getFilePath(),
            ).PHP_EOL);

            if ($error->getSource() !== null) {
                fwrite($output, sprintf(
                    '     %s',
                    $error->getSource()->getMessage(),
                ).PHP_EOL);
            }
        }
    }
}
