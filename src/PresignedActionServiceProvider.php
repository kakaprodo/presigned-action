<?php

namespace Kakaprodo\PresignedAction;

use Illuminate\Support\ServiceProvider;
use Kakaprodo\PresignedAction\Console\GenerateTemporaryAccessKeyCommand;

use Kakaprodo\PresignedAction\PresignedActionGate;

class PresignedActionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/presigned-action.php',
            'presigned-action'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();

        $this->stackToPublish();
    }

    protected function registerCommands()
    {
        if (!$this->app->runningInConsole()) return;

        $this->app->singleton(PresignedActionGate::class, function () {
            return new PresignedActionGate();
        });

        $this->commands([
            GenerateTemporaryAccessKeyCommand::class,
        ]);
    }


    public function stackToPublish()
    {
        $this->publishes([
            __DIR__ . '/config/presigned-action.php' => config_path('presigned-action.php'),
        ], 'config-presigned-action');

        if (config('presigned-action.should_run_migration', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        }
    }
}
