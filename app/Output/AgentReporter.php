<?php

namespace App\Output;

use App\Project;
use PhpCsFixer\Console\Report\FixReport\ReporterInterface;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Error\ErrorsManager;

final class AgentReporter implements ReporterInterface
{
    /**
     * Creates a new Agent Reporter instance.
     */
    public function __construct(
        protected ?ErrorsManager $errors = null,
    ) {
        //
    }

    /**
     * Get the format's name.
     */
    public function getFormat(): string
    {
        return 'agent';
    }

    /**
     * Process changed files array and returns generated report.
     */
    public function generate(ReportSummary $reportSummary): string
    {
        $changed = $reportSummary->getChanged();
        $errors = $this->getErrors();

        $projectPath = Project::path().DIRECTORY_SEPARATOR;

        $result = match (true) {
            $errors !== [] => 'fail',
            $changed === [] => 'pass',
            $reportSummary->isDryRun() => 'fail',
            default => 'fixed',
        };

        $output = ['result' => $result];

        if ($changed !== []) {
            $output['files'] = [];

            foreach ($changed as $path => $change) {
                $output['files'][] = [
                    'path' => str_replace($projectPath, '', $path),
                    'fixers' => $change['appliedFixers'],
                ];
            }
        }

        if ($errors !== []) {
            $output['errors'] = [];

            foreach ($errors as $error) {
                $output['errors'][] = [
                    'path' => str_replace($projectPath, '', $error->getFilePath()),
                    'message' => $error->getSource()->getMessage(),
                ];
            }
        }

        return json_encode($output, JSON_THROW_ON_ERROR);
    }

    /**
     * Get all errors from the errors manager.
     *
     * @return array<int, \PhpCsFixer\Error\Error>
     */
    protected function getErrors(): array
    {
        if ($this->errors === null) {
            return [];
        }

        return [
            ...$this->errors->getInvalidErrors(),
            ...$this->errors->getExceptionErrors(),
            ...$this->errors->getLintErrors(),
        ];
    }
}
