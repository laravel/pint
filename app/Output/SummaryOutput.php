<?php

namespace App\Output;

use App\Output\Concerns\InteractsWithSymbols;
use App\Project;
use App\ValueObjects\Issue;
use PhpCsFixer\Runner\Event\FileProcessed;

use function Termwind\render;
use function Termwind\renderUsing;

class SummaryOutput
{
    use InteractsWithSymbols;

    /**
     * The list of presets, in a human-readable format.
     *
     * @var array<string, string>
     */
    protected $presets = [
        'per' => 'PER',
        'psr12' => 'PSR 12',
        'laravel' => 'Laravel',
        'symfony' => 'Symfony',
        'empty' => 'Empty',
    ];

    /**
     * Creates a new Summary Output instance.
     *
     * @param  \App\Repositories\ConfigurationJsonRepository  $config
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(
        protected $config,
        protected $errors,
        protected $input,
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

        $issues = $this->getIssues(Project::path(), $summary);

        render(
            view('summary', [
                'totalFiles' => $totalFiles,
                'issues' => $issues,
                'testing' => $summary->isDryRun(),
                'preset' => $this->presets[$this->config->preset()],
            ]),
        );

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

        $this->output->writeln('');
    }

    /**
     * Gets the list of issues from the given summary.
     *
     * @param  string  $path
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $summary
     * @return \Illuminate\Support\Collection<int, \App\ValueObjects\Issue>
     */
    public function getIssues($path, $summary)
    {
        $issues = collect($summary->getChanged())
            ->map(fn ($information, $file) => new Issue(
                $path,
                $file,
                $this->getSymbol(FileProcessed::STATUS_FIXED),
                $information,
            ))
            ->values();

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
