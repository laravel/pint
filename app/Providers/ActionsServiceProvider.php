<?php

namespace App\Providers;

use App\Actions\ElaborateSummary;
use App\Actions\FixCode;
use App\Output\ProgressOutput;
use App\Output\SummaryOutput;
use App\Repositories\ConfigurationJsonRepository;
use Illuminate\Support\ServiceProvider;
use PhpCsFixer\Error\ErrorsManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ActionsServiceProvider extends ServiceProvider
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
        $this->app->singleton(FixCode::class, function () {
            return new FixCode(
                resolve(ErrorsManager::class),
                resolve(EventDispatcher::class),
                resolve(InputInterface::class),
                resolve(OutputInterface::class),
                new ProgressOutput(
                    resolve(EventDispatcher::class),
                    resolve(InputInterface::class),
                    resolve(OutputInterface::class),
                )
            );
        });

        $this->app->singleton(ElaborateSummary::class, function () {
            return new ElaborateSummary(
                resolve(ErrorsManager::class),
                resolve(InputInterface::class),
                resolve(OutputInterface::class),
                new SummaryOutput(
                    resolve(ConfigurationJsonRepository::class),
                    resolve(ErrorsManager::class),
                    resolve(InputInterface::class),
                    resolve(OutputInterface::class),
                )
            );
        });
    }
}
