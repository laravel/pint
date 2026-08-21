<?php

namespace App\Contracts;

interface HasFinderNames
{
    /**
     * The finder name patterns the rule needs the finder to include, on top
     * of the "*.php" files php-cs-fixer already discovers.
     *
     * @return array<int, string>
     */
    public function finderNames(): array;
}
