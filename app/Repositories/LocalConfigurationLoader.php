<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ConfigurationLoader;

class LocalConfigurationLoader implements ConfigurationLoader
{
    public function load(?string $path): ?string
    {
        if (is_null($path) || ! file_exists($path)) {
            return null;
        }

        return file_get_contents($path);
    }
}
