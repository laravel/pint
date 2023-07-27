<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ConfigurationLoader;
use Illuminate\Support\Facades\Http;

class RemoteConfigurationLoader implements ConfigurationLoader
{
    public function load(?string $path): ?string
    {
        if (is_null($path)) {
            return null;
        }

        $request = Http::get($path);

        if (! $request->ok()) {
            return null;
        }

        return $request->body();
    }
}
