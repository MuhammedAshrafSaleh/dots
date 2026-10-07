<?php

use App\Support\Locale;
use Illuminate\Support\Facades\Route;

// Bare "/" (and any locale-less visit) goes to the default locale.
Route::get('/', fn () => redirect('/'.Locale::DEFAULT));

Route::prefix('{locale}')
    ->whereIn('locale', Locale::SUPPORTED)
    ->middleware('setlocale')
    ->group(function (): void {
        Route::get('/', fn () => view('welcome'))->name('home');
    });
