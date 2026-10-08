<?php

use App\Http\Controllers\Admin\SessionController;
use App\Livewire\Admin\Attributes\Edit as AttributeEdit;
use App\Livewire\Admin\Attributes\Index as AttributeIndex;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Brands\Edit as BrandEdit;
use App\Livewire\Admin\Brands\Index as BrandIndex;
use App\Livewire\Admin\Dashboard;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin area (/admin)
|--------------------------------------------------------------------------
|
| Not locale-prefixed: the admin locale lives in the session (SetAdminLocale)
| and the language switch flips it. Every authenticated screen sits behind
| `admin.auth` (active admin + makes `admin` the default guard) plus a named
| `can:` permission, so new roles can be scoped without rewriting routes.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware('setadminlocale')
    ->group(function (): void {
        // Guest screens.
        Route::middleware('guest:admin')->group(function (): void {
            Route::get('login', Login::class)->name('login');
            Route::get('forgot-password', ForgotPassword::class)->name('password.request');
            Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
        });

        // Language switch + logout work regardless of auth state.
        Route::get('lang/{locale}', [SessionController::class, 'language'])
            ->whereIn('locale', Locale::SUPPORTED)
            ->name('lang');
        Route::post('logout', [SessionController::class, 'logout'])->name('logout');

        // Authenticated admin screens.
        Route::middleware('admin.auth')->group(function (): void {
            Route::get('/', Dashboard::class)
                ->middleware('can:dashboard.view')
                ->name('dashboard');

            // Catalog — Attributes (T3.3).
            Route::get('attributes', AttributeIndex::class)->middleware('can:attributes.view')->name('attributes');
            Route::get('attributes/create', AttributeEdit::class)->middleware('can:attributes.create')->name('attributes.create');
            Route::get('attributes/{attribute}/edit', AttributeEdit::class)->middleware('can:attributes.update')->name('attributes.edit');

            // Catalog — Brands (T3.4).
            Route::get('brands', BrandIndex::class)->middleware('can:brands.view')->name('brands');
            Route::get('brands/create', BrandEdit::class)->middleware('can:brands.create')->name('brands.create');
            Route::get('brands/{brand}/edit', BrandEdit::class)->middleware('can:brands.update')->name('brands.edit');
        });
    });
