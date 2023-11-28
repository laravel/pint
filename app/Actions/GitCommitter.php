<?php

namespace App\Actions;

use Illuminate\Console\Command;
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
                    <span class="text-green-500">No changes to commit!</span>
                </div>
                HTML
            );

            return Command::SUCCESS;
        }

        return $this->makeCommit($files);
    }

    /**
     * Make the Git commit.
     *
     * @param  array<string>  $files
     * @return int
     */
    protected function makeCommit(array $files)
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

        render(<<<'HTML'
            <div class="mx-2">
                <span class="text-green-500">Changes committed successfully!</span>
            </div>
            HTML
        );

        return Command::SUCCESS;
    }
}
