<?php

declare(strict_types=1);

namespace PhilipRehberger\ApiVersioning;

use Illuminate\Support\ServiceProvider;

class ApiVersioningServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-versioning.php' => config_path('api-versioning.php'),
            ], 'api-versioning-config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-versioning.php',
            'api-versioning'
        );
    }
}
