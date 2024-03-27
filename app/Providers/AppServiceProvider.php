<?php

namespace App\Providers;

use App\BladeFormatter;
use App\Fixers\LaravelBlade\Processors\IgnoreCode;
use App\Fixers\LaravelBlade\Processors\OneLinerSvg;
use App\NodeSandbox;
use App\Prettier;
use Illuminate\Support\ServiceProvider;
use Phar;
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
            return new ErrorsManager();
        });

        $this->app->singleton(EventDispatcher::class, function () {
            return new EventDispatcher();
        });

        $this->app->singleton(NodeSandbox::class, function () {
            return new NodeSandbox(
                Phar::running()
                    ? (dirname(Phar::running(false), 2).'/node_sandbox')
                    : base_path('node_sandbox'),
            );
        });

        $this->app->singleton(Prettier::class, function ($app) {
            return new Prettier($app->make(NodeSandbox::class));
        });

        $this->app->terminating(function () {
            $this->app->make(Prettier::class)->ensureTerminated();
        });

        $this->app->bind(BladeFormatter::class, function ($app) {
            return new BladeFormatter(
                $app->make(Prettier::class),
                collect([
                    OneLinerSvg::class,
                    IgnoreCode::class,
                ])->map(fn ($processor) => $app->make($processor))->all(),
            );
        });
    }
}
