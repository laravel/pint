<?php

namespace App\Output;

use App\Output\Concerns\InteractsWithSymbols;
use App\ValueObjects\Issue;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Error\Error;
use PhpCsFixer\FixerFileProcessedEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class Footer
{
    use InteractsWithSymbols;

    /**
     * The list of presets, on a human readable format.
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
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(
        protected $input,
        protected $output,
    ) {
        // ..
    }

    /**
     * Handle the given report summary.
     *
     * @param  \PhpCsFixer\Error\ErrorsManager  $errorsManager
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $reportSummary
     * @param  string  $path
     * @param  int  $total
     * @return void
     */
    public function handle($errorsManager, $reportSummary, $path, $total)
    {
        renderUsing($this->output);

        render(
            view('footer', [
                'total' => $total,
                'issues' => $this->getIssues($path, $errorsManager, $reportSummary),
                'pretending' => $reportSummary->isDryRun(),
                'isVerbose' => $this->output->isVerbose(),
                'preset' => $this->presets[(string) $this->input->getOption('preset')],
            ]),
        );
    }

    /**
     * Get "issues" from the errors and summary.
     *
     * @param  \PhpCsFixer\Error\ErrorsManager  $errorsManager
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $reportSummary
     * @return \Illuminate\Support\Collection<int, Issue>
     */
    public function getIssues($path, $errorsManager, $reportSummary)
    {
        $issues = collect($reportSummary->getChanged())
            ->map(fn ($information, $file) => new Issue(
                $path,
                $file,
                $this->getSymbol(FixerFileProcessedEvent::STATUS_FIXED),
                $information,
            ));

        return $issues->merge(
            collect(
                $errorsManager->getInvalidErrors()
                + $errorsManager->getExceptionErrors()
                + $errorsManager->getLintErrors()
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
