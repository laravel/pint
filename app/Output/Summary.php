<?php

namespace App\Output;

use App\ValueObjects\Change;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class Summary
{
    /**
     * Creates a new summary instance.
     */
    public function __construct(
        protected OutputInterface $output,
    ) {
        // ..
    }

    /**
     * Handle the given report summary.
     *
     * @param  \PhpCsFixer\Console\Report\FixReport\ReportSummary  $reportSummary
     * @param  string  $path
     * @param  int  $total
     * @return void
     */
    public function handle($reportSummary, $path, $total)
    {
        renderUsing($this->output);

        render(
            view('summary', [
                'total' => $total,
                'changes' => collect($reportSummary->getChanged())
                    ->map(fn ($information, $file) => new Change($path, $file, $information))
                    ->values(),
                'errors' => [],
                'isDryRun' => $reportSummary->isDryRun(),
                'isVerbose' => $this->output->isVerbose(),
            ]),
        );
    }
}
