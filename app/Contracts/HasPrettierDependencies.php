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
}
