<?php

namespace Kakaprodo\PresignedAction;

use Illuminate\Support\ServiceProvider;

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

        $this->commands([]);
    }


    public function stackToPublish()
    {
        $this->publishes([
            __DIR__ . '/config/presigned-action.php' => config_path('presigned-action.php'),
        ], 'config-presigned-action');
    }
}
