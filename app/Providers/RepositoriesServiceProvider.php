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

            if (file_exists(Project::path().'/pint.php') && ! file_exists(Project::path().'/pint.json')) {
                $config = Project::path().'/pint.php';
            } else {
                $config = Project::path().'/pint.json';
            }

            return new ConfigurationJsonRepository(
                $input->getOption('config') ?: $config,
                $input->getOption('preset'),
            );
        });
    }
}
