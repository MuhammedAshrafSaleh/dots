<?php

use App\Support\Locale;

it('redirects the bare root to the default locale', function () {
    $this->get('/')->assertRedirect('/ar');
});

it('renders Arabic with rtl direction', function () {
    $this->get('/ar')
        ->assertOk()
        ->assertSee('lang="ar-EG"', false)
        ->assertSee('dir="rtl"', false);
});

it('renders English with ltr direction', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false);
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
