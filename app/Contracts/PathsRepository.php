<?php

namespace App\Contracts;

interface PathsRepository
{
    /**
     * Determine the "dirty" files.
     *
     * @return array<int, string>
     */
    public function dirty();

    /**
     * Determine the files that have changed since branching off from the given branch.
     *
     * @param  string  $branch
     * @return array<int, string>
     */
    public function diff($branch);
}
