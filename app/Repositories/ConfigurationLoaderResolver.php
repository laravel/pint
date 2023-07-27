<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ConfigurationLoader;

class ConfigurationLoaderResolver
{
    public function resolveFor(?string $configPath): ConfigurationLoader
    {
        return match (true) {
            str_starts_with($configPath ?: '', 'http') => resolve(RemoteConfigurationLoader::class),
            default => resolve(LocalConfigurationLoader::class)
        };
    }
}
