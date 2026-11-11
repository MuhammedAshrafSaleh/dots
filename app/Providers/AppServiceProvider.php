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
use App\Notifications\Channels\LogChannel;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\PageSectionObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\SettingObserver;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymobGateway;
use App\Services\Settings;
use App\Support\Wishlist;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->scoped(Wishlist::class);
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

        // Non-blocking Livewire script: injected at end of </body> by default;
        // defer lets the browser fetch it concurrently with HTML parsing.
        // Alpine (bundled in Livewire) starts after DOM parse — correct order.
        Livewire::useScriptTagAttributes(['defer' => true]);

        // Admin Livewire components update via POST /livewire/update, which runs
        // Livewire's own middleware group — NOT the admin route middleware. Mark
        // the admin guard + locale middleware persistent so they re-run on every
        // component update; otherwise authorize()/can() inside actions sees no
        // admin user and returns 403.
        Livewire::addPersistentMiddleware([
            AdminAuth::class,
            SetAdminLocale::class,
        ]);

        // Spatie's global upload backstop defaults to 10 MB and throws
        // FileIsTooBig at toMediaCollection() time — AFTER our Livewire
        // validation has already passed — which surfaces as a 500 rather than a
        // field error. Align it with the largest size our forms validate (the
        // 50 MB hero video) so per-field validation stays the user-facing gate;
        // the per-field rules keep images small. Must stay >= that ceiling.
        config(['media-library.max_file_size' => 1024 * 1024 * 50]);

        // Cache-column maintenance + tagged cache invalidation (observers are
        // resolved from the container, so their dependencies are injected).
        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
        PageSection::observe(PageSectionObserver::class);
        Setting::observe(SettingObserver::class);

        $this->bootBackupSafeguards();
    }

    /**
     * Off-site backup safeguards (BK.2).
     */
    private function bootBackupSafeguards(): void
    {
        // Register the custom "log" notification channel so spatie/laravel-
        // backup can write failure notifications to the application log (mail
        // is not configured until Phase 10 step 6).
        $this->app->make(ChannelManager::class)
            ->extend('log', fn ($app) => $app->make(LogChannel::class));

        // Refuse to run `backup:run` without an archive password: encryption is
        // mandatory for off-site backups, and an empty password would otherwise
        // write an UNENCRYPTED archive to Google Drive. Fail loudly instead.
        Event::listen(function (CommandStarting $event): void {
            if ($event->command !== 'backup:run') {
                return;
            }

            if (filled(config('backup.backup.password'))) {
                return;
            }

            throw new RuntimeException(
                'BACKUP_ARCHIVE_PASSWORD is not set. Refusing to create an '
                .'unencrypted off-site backup. Set a strong password in .env '
                .'and keep it safe — losing it makes every existing backup '
                .'permanently unreadable (see docs/BACKUP.md).'
            );
        });
    }
}
