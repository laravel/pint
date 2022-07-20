<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PhpCsFixer\Console\Report\FixReport\CheckstyleReporter;
use PhpCsFixer\Console\Report\FixReport\GitlabReporter;
use PhpCsFixer\Console\Report\FixReport\JsonReporter;
use PhpCsFixer\Console\Report\FixReport\JunitReporter;
use PhpCsFixer\Console\Report\FixReport\TextReporter;
use PhpCsFixer\Console\Report\FixReport\XmlReporter;
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

        $this->app->singleton(CheckstyleReporter::class, fn () => new CheckstyleReporter());
        $this->app->singleton(GitlabReporter::class, fn () => new GitlabReporter());
        $this->app->singleton(JsonReporter::class, fn () => new JsonReporter());
        $this->app->singleton(JunitReporter::class, fn () => new JunitReporter());
        $this->app->singleton(TextReporter::class, fn () => new TextReporter());
        $this->app->singleton(XmlReporter::class, fn () => new XmlReporter());

        $this->app->tag([
            CheckstyleReporter::class,
            GitlabReporter::class,
            JsonReporter::class,
            JunitReporter::class,
            TextReporter::class,
            XmlReporter::class,
        ], 'reporters');
    }
}
