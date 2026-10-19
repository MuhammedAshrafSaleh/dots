<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Account\SessionController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandDetailController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\PaymobReturnController;
use App\Http\Controllers\PaymobWebhookController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ProjectDetailController;
use App\Http\Controllers\ProjectsCurrentController;
use App\Http\Controllers\ProjectsPreviousController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceDetailController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\WhatsAppController;
use App\Livewire\Account\Addresses;
use App\Livewire\Account\Auth\ForgotPassword;
use App\Livewire\Account\Auth\Login;
use App\Livewire\Account\Auth\Register;
use App\Livewire\Account\Auth\ResetPassword;
use App\Livewire\Account\OrderDetail;
use App\Livewire\Account\Orders;
use App\Livewire\Account\Overview;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Wishlist;
use App\Support\Locale;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Admin area (/admin) — not locale-prefixed; see routes/admin.php.
require __DIR__.'/admin.php';

// Bare "/" (and any locale-less visit) goes to the default locale.
Route::get('/', fn () => redirect('/'.Locale::DEFAULT));

// SEO files — served outside the locale prefix.
Route::get('/robots.txt', function () {
    $sitemap = url('/sitemap.xml');

    return response(
        "User-agent: *\nAllow: /\nDisallow: /admin\n\nSitemap: {$sitemap}\n",
        200,
        ['Content-Type' => 'text/plain']
    );
});

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (! file_exists($path)) {
        Artisan::call('sitemap:generate');
    }

    return response()->file($path, ['Content-Type' => 'application/xml']);
});

// Paymob server→server webhook — not locale-prefixed, CSRF-exempt (see bootstrap/app.php).
Route::post('/payments/paymob/webhook', PaymobWebhookController::class)->name('paymob.webhook');

Route::prefix('{locale}')
    ->whereIn('locale', Locale::SUPPORTED)
    ->middleware('setlocale')
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');

        Route::get('/wa/{source}', [WhatsAppController::class, 'redirect'])
            ->name('wa.redirect');

        // Customer accounts (Phase 8). Guest-only auth screens on the customer
        // guard; logout works regardless of state.
        Route::middleware('guest:customer')->group(function (): void {
            Route::get('/login', Login::class)->name('login');
            Route::get('/register', Register::class)->name('register');
            Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
            Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
        });
        Route::post('/logout', [SessionController::class, 'logout'])->name('logout');

        // Signed-in account area (T8.3). Every screen requires the customer
        // guard; order detail is scoped to the owner inside the component.
        Route::middleware('auth:customer')
            ->prefix('account')
            ->name('account.')
            ->group(function (): void {
                Route::get('/', Overview::class)->name('overview');
                Route::get('/orders', Orders::class)->name('orders');
                Route::get('/orders/{order:number}', OrderDetail::class)->name('order');
                Route::get('/wishlist', Wishlist::class)->name('wishlist');
                Route::get('/addresses', Addresses::class)->name('addresses');
                Route::get('/profile', Profile::class)->name('profile');
            });

        // Placeholder public pages. The global layout (T5.1) links to all of
        // these; each is replaced by its real screen in a later Phase 5/6 task.
        $comingSoon = fn () => view('site.coming-soon');

        Route::get('/products', ProductsController::class)->name('products');
        Route::get('/products/{product}', ProductDetailController::class)->name('product.show');
        Route::get('/cart', CartController::class)->name('cart');
        Route::get('/checkout', CheckoutController::class)->name('checkout');
        Route::get('/order/{number}/confirmation', OrderConfirmationController::class)->name('order.confirmation');
        Route::get('/order/{number}/payment-failed', fn (string $locale, string $number) => view('site.payment-failed', compact('locale', 'number')))->name('order.payment-failed');
        Route::get('/payments/paymob/return', PaymobReturnController::class)->name('paymob.return');
        Route::get('/brands', BrandsController::class)->name('brands');
        Route::get('/brands/{brand}', BrandDetailController::class)->name('brand.show');
        Route::get('/services', ServicesController::class)->name('services');
        Route::get('/services/{slug}', ServiceDetailController::class)->name('service.show');
        Route::get('/projects', ProjectsCurrentController::class)->name('projects.current');
        Route::get('/projects/previous', ProjectsPreviousController::class)->name('projects.previous');
        Route::get('/projects/{slug}', ProjectDetailController::class)->name('project.show');
        Route::get('/blog', BlogController::class)->name('blog');
        Route::get('/blog/{slug}', ArticleController::class)->name('blog.show');
        Route::get('/about', AboutController::class)->name('about');
        Route::get('/track-order', fn (string $locale) => view('site.track', compact('locale')))->name('track');
        Route::get('/search', SearchController::class)->name('search');
        Route::get('/c/{category}', CategoryController::class)->name('category');
        Route::get('/p/{page}', ContentPageController::class)->name('page');

        // Newsletter subscribe — real handler + dedupe lands in T5.7.
        Route::get('/newsletter/subscribe', $comingSoon)->name('newsletter.subscribe');

        // Design styleguide + component kitchen sink — never in production.
        if (! app()->isProduction()) {
            Route::get('/_styleguide', fn () => view('styleguide'))->name('styleguide');
            Route::get('/_kitchen-sink', fn () => view('kitchen-sink'))->name('kitchen-sink');
        }
    });
