<?php

namespace App\Providers;

use App\Repositories\ConfigurationLoaderResolver;
use App\Repositories\LocalConfigurationLoader;
use App\Repositories\RemoteConfigurationLoader;
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
        $this->app->singleton(ErrorsManager::class, fn () => new ErrorsManager());

        $this->app->singleton(EventDispatcher::class, fn () => new EventDispatcher());

        $this->app->singleton(ConfigurationLoaderResolver::class, fn () => new ConfigurationLoaderResolver());
        $this->app->Singleton(RemoteConfigurationLoader::class, fn () => new RemoteConfigurationLoader());
        $this->app->Singleton(LocalConfigurationLoader::class, fn () => new LocalConfigurationLoader());
    }
}
