<?php

namespace App\Output;

use App\Output\Concerns\InteractsWithSymbols;
use App\ValueObjects\Issue;
use PhpCsFixer\FixerFileProcessedEvent;
use function Termwind\render;
use function Termwind\renderUsing;

class SummaryOutput
{
    use InteractsWithSymbols;

    /**
     * The list of presets, on an human readable format.
     *
     * @var array<string, string>
     */
    protected $presets = [
        'psr12' => 'PSR 12',
        'laravel' => 'Laravel',
    ];

    /**
     * Creates a new Footer instance.
     *
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(
        protected $errors,
        protected $input,
        protected $config,
        protected $output,
    ) {
        // ..
    }

    /**
     * Handle the given report summary.
     *
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $summary
     * @param  int  $totalFiles
     * @return void
     */
    public function handle($summary, $totalFiles)
    {
        renderUsing($this->output);

        $issues = $this->getIssues((string) $this->input->getArgument('path'), $summary);

        render(
            view('summary', [
                'totalFiles' => $totalFiles,
                'issues' => $issues,
                'testing' => $summary->isDryRun(),
                'preset' => $this->presets[$this->config->preset()],
            ]),
        );

        if ($issues->isEmpty()) {
            $this->output->writeln('');
        }

        foreach ($issues as $issue) {
            render(view('issue.show', [
                'issue' => $issue,
                'isVerbose' => $this->output->isVerbose(),
                'testing' => $summary->isDryRun(),
            ]));

            if ($this->output->isVerbose() && $issue->code()) {
                $this->output->writeln(
                    $issue->code(),
                );
            }
        }
    }

    /**
     * Get "issues" from the errors and summary.
     *
     * @param  string  $path
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $summary
     * @return \Illuminate\Support\Collection<int, Issue>
     */
    public function getIssues($path, $summary)
    {
        $issues = collect($summary->getChanged())
            ->map(fn ($information, $file) => new Issue(
                $path,
                $file,
                $this->getSymbol(FixerFileProcessedEvent::STATUS_FIXED),
                $information,
            ));

        return $issues->merge(
            collect(
                $this->errors->getInvalidErrors()
                + $this->errors->getExceptionErrors()
                + $this->errors->getLintErrors()
            )->map(fn ($error) => new Issue(
                $path,
                $error->getFilePath(),
                $this->getSymbolFromErrorType($error->getType()),
                [
                    'source' => $error->getSource(),
                ],
            )),
        )->sort(function ($issueA, $issueB) {
            return $issueA <=> $issueB;
        })->values();
    }
}
