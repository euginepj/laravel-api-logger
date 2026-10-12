<?php

namespace Euginepj\ApiLogger;

use Euginepj\ApiLogger\Commands\PruneApiLogsCommand;
use Euginepj\ApiLogger\Http\Middleware\LogApiRequests;
use Illuminate\Support\ServiceProvider;

class ApiLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-logger.php',
            'api-logger'
        );

        $this->app->singleton(LogApiRequests::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/api-logger.php' => config_path('api-logger.php'),
        ], 'api-logger-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'api-logger-migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-logger');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/api-logger'),
        ], 'api-logger-views');

        // Load web dashboard routes
        if (config('api-logger.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneApiLogsCommand::class,
            ]);
        }
    }
}
