<?php

use App\Support\Locale;

beforeEach(fn () => $this->withoutVite());

it('redirects the bare root to the default locale', function () {
    $this->get('/')->assertRedirect('/ar');
});

it('renders Arabic with rtl direction and the global layout', function () {
    $this->get('/ar')
        ->assertOk()
        ->assertSee('lang="ar-EG"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('d-hi', false)                        // home hero section (T5.3)
        ->assertSee('d-nav', false)                       // global header (T5.1)
        ->assertSee(__('ui.track_order', [], 'ar'));      // locale-aware nav label
});

it('renders English with ltr direction and the global layout', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('d-hi', false)                        // home hero section (T5.3)
        ->assertSee('d-nav', false)                       // global header (T5.1)
        ->assertSee('Track order');                       // locale-aware nav label
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
