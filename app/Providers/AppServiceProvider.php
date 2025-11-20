<?php

namespace App\Providers;

use App\Services\PresetManifest;
use Illuminate\Support\ServiceProvider;
use PhpCsFixer\Error\ErrorsManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ErrorsManager::class, function () {
            return new ErrorsManager;
        });

        $this->app->singleton(EventDispatcher::class, function () {
            return new EventDispatcher;
        });

        $this->app->singleton(PresetManifest::class, function ($app) {
            return new PresetManifest(
                $app->make('files'),
                $app->basePath(),
                $app->basePath('bootstrap/cache/pint_presets.php'),
            );
        });
    }
}
