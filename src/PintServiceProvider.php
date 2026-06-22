<?php

namespace Laravel\Pint;

use Illuminate\Support\ServiceProvider;

class PintServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PintCommand::class,
            ]);
        }
    }
}
