<?php

namespace App\Providers;

use App\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Strict models everywhere but production: fail loudly on lazy loading,
        // missing attributes and silently-discarded attributes during dev/tests.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
