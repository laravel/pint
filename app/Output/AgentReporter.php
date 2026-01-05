<?php

namespace App\Output;

use App\Project;
use PhpCsFixer\Console\Report\FixReport\ReporterInterface;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;

final class AgentReporter implements ReporterInterface
{
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

        $projectPath = Project::path().DIRECTORY_SEPARATOR;

        $status = match (true) {
            $changed === [] => 'pass',
            $reportSummary->isDryRun() => 'fail',
            default => 'fixed',
        };

        $output = ['result' => $status];

        if ($changed !== []) {
            $output['files'] = [];

            foreach ($changed as $path => $change) {
                $output['files'][] = [
                    'path' => str_replace($projectPath, '', $path),
                    'fixers' => $change['appliedFixers'],
                ];
            }
        }

        return json_encode($output, JSON_THROW_ON_ERROR);
    }
}
