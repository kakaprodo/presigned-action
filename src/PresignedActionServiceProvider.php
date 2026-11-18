<?php

namespace Kakaprodo\PresignedAction;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Kakaprodo\PresignedAction\Console\GenerateTemporaryAccessKeyCommand;
use Kakaprodo\PresignedAction\Console\PurgeExpiredTemporaryAccessKeysCommand;
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

        $this->app->singleton(PresignedActionGate::class, fn () => new PresignedActionGate());
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();
        $this->registerTempAuthCodeRateLimiter();

        if (config('presigned-action.temp_auth_code.route.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        }

        $this->stackToPublish();
    }

    protected function registerCommands()
    {
        if (!$this->app->runningInConsole()) return;

        $this->commands([
            GenerateTemporaryAccessKeyCommand::class,
            PurgeExpiredTemporaryAccessKeysCommand::class,
        ]);
    }

    /** Register the configurable unauthenticated-code generation limiter. */
    protected function registerTempAuthCodeRateLimiter(): void
    {
        if (! config('presigned-action.temp_auth_code.rate_limit.enabled', true)) {
            return;
        }

        RateLimiter::for('presigned-action-temp-auth-code', function ($request) {
            $config = config('presigned-action.temp_auth_code.rate_limit', []);
            $resolver = $config['key_resolver'] ?? null;
            $key = is_callable($resolver) ? $resolver($request) : $request->ip();

            return new Limit(
                $key,
                (int) ($config['max_attempts'] ?? 20),
                (int) ($config['window_seconds'] ?? 60)
            );
        });
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
