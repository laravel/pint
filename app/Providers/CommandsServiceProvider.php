<?php

namespace App\Providers;

use App\Actions\ElaborateSummary;
use App\Actions\FixCode;
use App\Commands\DefaultCommand;
use Illuminate\Support\ServiceProvider;

class CommandsServiceProvider extends ServiceProvider
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
        $this->app->bindMethod([DefaultCommand::class, 'handle'], function ($command) {
            return $command->handle(
                resolve(FixCode::class),
                resolve(ElaborateSummary::class)
            );
        });
    }
}
