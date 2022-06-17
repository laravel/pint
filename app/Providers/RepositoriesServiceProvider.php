<?php

namespace App\Providers;

use App\Repositories\ConfigurationJsonRepository;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\InputInterface;

class RepositoriesServiceProvider extends ServiceProvider
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
        $this->app->singleton(ConfigurationJsonRepository::class, function () {
            return new ConfigurationJsonRepository(
                resolve(InputInterface::class)->getArgument('path') ?: (string) getcwd(),
            );
        });
    }
}
