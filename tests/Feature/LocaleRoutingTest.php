<?php

use App\Support\Locale;

beforeEach(fn () => $this->withoutVite());

it('redirects the bare root to the default locale', function () {
    $this->get('/')->assertRedirect('/ar');
});

it('renders Arabic with rtl direction and the placeholder shell', function () {
    $this->get('/ar')
        ->assertOk()
        ->assertSee('lang="ar-EG"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee(__('ui.under_construction', [], 'ar'))
        ->assertSee(__('ui.all_rights_reserved', [], 'ar'));
});

it('renders English with ltr direction and the placeholder shell', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('DOTS — under construction')
        ->assertSee('All rights reserved.');
});

it('404s on an unsupported locale', function () {
    $this->get('/fr')->assertNotFound();
});

it('keeps the path when switching locale on a nested url', function () {
    expect(Locale::switchUrl('en', '/ar/products/lamp-123?sort=price'))
        ->toBe('/en/products/lamp-123?sort=price');

    expect(Locale::switchUrl('ar', '/en/about'))
        ->toBe('/ar/about');
});

it('prefixes a locale-less path when switching', function () {
    expect(Locale::switchUrl('en', '/'))->toBe('/en');
});
