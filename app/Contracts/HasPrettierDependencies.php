<?php

namespace App\Contracts;

interface HasPrettierDependencies
{
    /**
     * The prettier dependencies (npm packages) the rule requires.
     *
     * @return array<int, string>
     */
    public function prettierDependencies(): array;
}
