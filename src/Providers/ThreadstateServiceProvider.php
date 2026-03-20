<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Threadable\StateMachine\Console\GenerateClasses;
use Threadable\StateMachine\Console\Install;
use Threadable\StateMachine\Console\MakeSpecification;
use Threadable\StateMachine\Events\StmStateableObjectStateChangeEvent;
use Threadable\StateMachine\Events\TransitionActionEvent;
use Threadable\StateMachine\Listeners\ActionListener;
use Threadable\StateMachine\Listeners\TransitionLogger;

class ThreadstateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/threadstate.php', 'threadstate');
    }

    public function boot(): void
    {
        $this->registerEventListeners();
        $this->loadMigrationsFrom($this->packageMigrationPath());
        $this->registerRoutes();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Install::class,
            MakeSpecification::class,
            GenerateClasses::class,
        ]);

        $this->publishesMigrations([
            $this->packageMigrationPath() => database_path('migrations'),
        ], 'threadstate-migrations');

        $this->publishes([
            dirname(__DIR__, 2).'/config/threadstate.php' => config_path('threadstate.php'),
        ], 'threadstate-config');
    }

    protected function registerEventListeners(): void
    {
        Event::listen(StmStateableObjectStateChangeEvent::class, TransitionLogger::class);
        Event::listen(TransitionActionEvent::class, ActionListener::class);
    }

    protected function packageMigrationPath(): string
    {
        return dirname(__DIR__).'/Resources/Database';
    }

    protected function registerRoutes(): void
    {
        if (! config('threadstate.routes.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(dirname(__DIR__, 2).'/routes/threadstate.php');
    }
}
