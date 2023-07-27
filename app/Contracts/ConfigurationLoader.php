<?php

declare(strict_types=1);

namespace App\Contracts;

interface ConfigurationLoader
{
    /**
     * Load the configuration file.
     *
     * @param  string  $path
     */
    public function load(?string $path): ?string;
}
