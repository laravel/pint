<?php

namespace App\Providers;

use App\Repositories\ConfigurationJsonRepository;
use App\Support\Project;
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
            $input = resolve(InputInterface::class);

            return new ConfigurationJsonRepository(
                $input->getOption('config') ?: Project::path().'/pint.json',
                $input->getOption('preset'),
            );
        });
    }
}
