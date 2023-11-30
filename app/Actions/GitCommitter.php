<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;

use function Termwind\render;

class GitCommitter
{
    /**
     * Creates a new Git Committer instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(
        protected $input,
        protected $output,
    ) {
        //
    }

    /**
     * Commit the changes to Git.
     *
     * @param  array<string, array{appliedFixers: array<int, string>, diff: string}>  $changes
     * @return int
     */
    public function execute($changes)
    {
        $files = array_keys($changes);

        if (empty($files)) {
            render(<<<'HTML'
                <div class="mx-2">
                    <span class="px-2 bg-green text-gray uppercase font-bold mr-1">Info:</span>
                    <span>Nothing to commit, working tree clean.</span>
                </div>
                HTML
            );

            return Command::SUCCESS;
        }

        return $this->commit($files);
    }

    /**
     * Make the Git commit.
     *
     * @param  array<int, string>  $files
     * @return int
     */
    protected function commit(array $files)
    {
        $process = Process::run(sprintf('git commit -m "Apply style fixes from Laravel Pint" %s', implode(' ', $files)));

        if ($process->failed()) {
            render(<<<HTML
                <div class="mx-2">
                    <span class="px-2 bg-red text-white uppercase font-bold mr-1">Error:</span>
                    <span>{$process->errorOutput()}</span>
                </div>
                HTML
            );

            return Command::FAILURE;
        }

        render(sprintf(<<<'HTML'
            <div class="mx-2">
                <span class="px-2 bg-green text-gray uppercase font-bold mr-1">Success:</span>
                <span>%s</span>
            </div>
            HTML, trim(Arr::last(explode("\n", trim($process->output())))))
        );

        return Command::SUCCESS;
    }
}
