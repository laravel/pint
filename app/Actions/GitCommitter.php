<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Termwind\render;

class GitCommitter
{
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
                <div class="mx-2 mb-1">
                <span class="px-2 bg-blue text-white uppercase font-bold mr-1">Info</span>
                    <span>Nothing to commit.</span>
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
                <div class="mx-2 mb-1">
                    <span class="px-2 bg-red text-white uppercase font-bold mr-1">Error</span>
                    <span>{$process->errorOutput()}</span>
                </div>
                HTML
            );

            return Command::FAILURE;
        }

        render(<<<'HTML'
            <div class="mx-2 mb-1">
                <span class="px-2 bg-blue text-white uppercase font-bold mr-1">Info</span>
                <span>Changes committed to Git.</span>
            </div>
            HTML
        );

        return Command::SUCCESS;
    }
}
