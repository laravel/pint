<?php

namespace App\Output;

use App\ValueObjects\Change;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class Summary
{
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
     * Creates a new summary instance.
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
                'preset' => $this->presets[(string) $this->input->getOption('preset')],
            ]),
        );
    }
}
