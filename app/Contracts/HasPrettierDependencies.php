<?php

namespace App\Contracts;

interface HasPrettierDependencies
{
    /**
     * The prettier dependencies (npm packages) the rule requires, mapped to
     * the semver constraint each package must satisfy.
     *
     * @return array<string, string>
     */
    public function prettierDependencies(): array;

    /**
     * The finder name patterns the rule needs the finder to include, on top
     * of the "*.php" files php-cs-fixer already discovers.
     *
     * @return array<int, string>
     */
    public function finderNames(): array;
}
