<?php

use App\Http\Controllers\WhatsAppController;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

// Admin area (/admin) — not locale-prefixed; see routes/admin.php.
require __DIR__.'/admin.php';

// Bare "/" (and any locale-less visit) goes to the default locale.
Route::get('/', fn () => redirect('/'.Locale::DEFAULT));

Route::prefix('{locale}')
    ->whereIn('locale', Locale::SUPPORTED)
    ->middleware('setlocale')
    ->group(function (): void {
        Route::get('/', fn () => view('welcome'))->name('home');

        Route::get('/wa/{source}', [WhatsAppController::class, 'redirect'])
            ->name('wa.redirect');

        // Placeholder public pages. The global layout (T5.1) links to all of
        // these; each is replaced by its real screen in a later Phase 5/6 task.
        $comingSoon = fn () => view('site.coming-soon');

        Route::get('/products', $comingSoon)->name('products');
        Route::get('/brands', $comingSoon)->name('brands');
        Route::get('/services', $comingSoon)->name('services');
        Route::get('/projects', $comingSoon)->name('projects.current');
        Route::get('/projects/previous', $comingSoon)->name('projects.previous');
        Route::get('/blog', $comingSoon)->name('blog');
        Route::get('/about', $comingSoon)->name('about');
        Route::get('/track-order', $comingSoon)->name('track');
        Route::get('/search', $comingSoon)->name('search');
        Route::get('/c/{category}', $comingSoon)->name('category');
        Route::get('/p/{page}', $comingSoon)->name('page');

        // Newsletter subscribe — real handler + dedupe lands in T5.7.
        Route::get('/newsletter/subscribe', $comingSoon)->name('newsletter.subscribe');

        // Design styleguide + component kitchen sink — never in production.
        if (! app()->isProduction()) {
            Route::get('/_styleguide', fn () => view('styleguide'))->name('styleguide');
            Route::get('/_kitchen-sink', fn () => view('kitchen-sink'))->name('kitchen-sink');
        }
    });
