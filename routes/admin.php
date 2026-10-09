<?php

use App\Http\Controllers\Admin\SessionController;
use App\Livewire\Admin\Attributes\Edit as AttributeEdit;
use App\Livewire\Admin\Attributes\Index as AttributeIndex;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Blog\Edit as BlogEdit;
use App\Livewire\Admin\Blog\Index as BlogIndex;
use App\Livewire\Admin\Blog\TaxIndex as BlogTaxIndex;
use App\Livewire\Admin\Brands\Edit as BrandEdit;
use App\Livewire\Admin\Brands\Index as BrandIndex;
use App\Livewire\Admin\PageContent\Edit as PageContentEdit;
use App\Livewire\Admin\Categories\Index as CategoryIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Products\Edit as ProductEdit;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Livewire\Admin\Projects\Edit as ProjectEdit;
use App\Livewire\Admin\Projects\Index as ProjectIndex;
use App\Livewire\Admin\Services\Edit as ServiceEdit;
use App\Livewire\Admin\Services\Index as ServiceIndex;
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

            // Catalog — Categories (T3.5).
            Route::get('categories', CategoryIndex::class)->middleware('can:categories.view')->name('categories');

            // Content — Services (T4.2).
            Route::get('services', ServiceIndex::class)->middleware('can:services.view')->name('services');
            Route::get('services/create', ServiceEdit::class)->middleware('can:services.create')->name('services.create');
            Route::get('services/{service}/edit', ServiceEdit::class)->middleware('can:services.update')->name('services.edit');

            // Content — Projects (T4.3).
            Route::get('projects', ProjectIndex::class)->middleware('can:projects.view')->name('projects');
            Route::get('projects/create', ProjectEdit::class)->middleware('can:projects.create')->name('projects.create');
            Route::get('projects/{project}/edit', ProjectEdit::class)->middleware('can:projects.update')->name('projects.edit');

            // Content — Blog (T4.4).
            Route::get('blog', BlogIndex::class)->middleware('can:posts.view')->name('blog');
            Route::get('blog/create', BlogEdit::class)->middleware('can:posts.create')->name('blog.create');
            Route::get('blog/taxonomy', BlogTaxIndex::class)->middleware('can:post_categories.manage')->name('blog.taxonomy');
            Route::get('blog/{post}/edit', BlogEdit::class)->middleware('can:posts.update')->name('blog.edit');

            // Content — Page sections (T4.1).
            Route::get('page-content/{page?}', PageContentEdit::class)
                ->middleware('can:page_content.view')
                ->name('page-content')
                ->defaults('page', 'home')
                ->whereIn('page', ['home', 'about', 'services', 'brands', 'projects', 'blog', 'global']);

            // Catalog — Products (T3.6).
            Route::get('products', ProductIndex::class)->middleware('can:products.view')->name('products');
            Route::get('products/create', ProductEdit::class)->middleware('can:products.create')->name('products.create');
            Route::get('products/{product}/edit', ProductEdit::class)->middleware('can:products.update')->name('products.edit');
        });
    });
