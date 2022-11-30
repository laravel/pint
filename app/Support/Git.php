<?php

namespace App\Support;

use Symfony\Component\Process\Process;

class Git
{
    /**
     * Determine the files which were added, modified,
     * copied, or renamed since the last commit.
     *
     * @return array<array<string>, bool>
     */
    public function dirtyFiles(): array
    {
        $process = new Process(['git', 'diff', '--name-only', '--diff-filter=AMCR', 'HEAD', '--', '*.php']);
        $process->run();

        return [
            preg_split('/\R+/', $process->getOutput(), flags: PREG_SPLIT_NO_EMPTY),
            $process->isSuccessful(),
        ];
    }
}
