<?php

namespace App\Providers;

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
