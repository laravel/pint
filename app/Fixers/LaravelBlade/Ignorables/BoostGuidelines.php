<?php

namespace App\Fixers\LaravelBlade\Ignorables;

class BoostGuidelines
{
    /**
     * Whether the given file content should be ignored.
     */
    public function __invoke(string $path): bool
    {
        return str_contains(str_replace('\\', '/', $path), 'resources/boost/guidelines/');
    }
}
