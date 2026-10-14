<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandDetailController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectDetailController;
use App\Http\Controllers\ProjectsCurrentController;
use App\Http\Controllers\ProjectsPreviousController;
use App\Http\Controllers\ServiceDetailController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\WhatsAppController;
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

Route::prefix('{locale}')
    ->whereIn('locale', Locale::SUPPORTED)
    ->middleware('setlocale')
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');

        Route::get('/wa/{source}', [WhatsAppController::class, 'redirect'])
            ->name('wa.redirect');

        // Placeholder public pages. The global layout (T5.1) links to all of
        // these; each is replaced by its real screen in a later Phase 5/6 task.
        $comingSoon = fn () => view('site.coming-soon');

        Route::get('/products', $comingSoon)->name('products');
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
        Route::get('/track-order', $comingSoon)->name('track');
        Route::get('/search', $comingSoon)->name('search');
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
