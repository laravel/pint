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
}
