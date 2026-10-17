<?php

namespace App\Providers;

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\SetAdminLocale;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\PageSectionObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\SettingObserver;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymobGateway;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->bind(PaymentGateway::class, PaymobGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Strict models everywhere but production: fail loudly on lazy loading,
        // missing attributes and silently-discarded attributes during dev/tests.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Admin Livewire components update via POST /livewire/update, which runs
        // Livewire's own middleware group — NOT the admin route middleware. Mark
        // the admin guard + locale middleware persistent so they re-run on every
        // component update; otherwise authorize()/can() inside actions sees no
        // admin user and returns 403.
        Livewire::addPersistentMiddleware([
            AdminAuth::class,
            SetAdminLocale::class,
        ]);

        // Cache-column maintenance + tagged cache invalidation (observers are
        // resolved from the container, so their dependencies are injected).
        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
        PageSection::observe(PageSectionObserver::class);
        Setting::observe(SettingObserver::class);
    }
}
