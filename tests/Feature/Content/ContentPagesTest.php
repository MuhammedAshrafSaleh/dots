<?php

use App\Models\Page;
use App\Support\Slug;

beforeEach(function () {
    $this->withoutVite();
});

function makePage(string $key, string $enTitle, string $arTitle, bool $footer = true): Page
{
    return Page::create([
        'key' => $key,
        'title' => ['en' => $enTitle, 'ar' => $arTitle],
        'slug' => Slug::fromTranslations(['en' => $enTitle, 'ar' => $arTitle]),
        'lede' => ['en' => 'Lede text.', 'ar' => 'نص مقدمة.'],
        'body' => ['en' => '<p>Body EN.</p>', 'ar' => '<p>Body AR.</p>'],
        'show_in_footer' => $footer,
        'sort_order' => 0,
        'is_active' => true,
    ]);
}

// ─────────────────────── EN pages ───────────────────────

it('renders the delivery page in EN', function () {
    makePage('delivery', 'Delivery', 'التوصيل');

    $this->get('/en/p/delivery')
        ->assertOk()
        ->assertSee('Delivery')
        ->assertSee('Body EN.');
});

it('renders all six content pages in EN', function () {
    makePage('delivery', 'Delivery', 'التوصيل');
    makePage('returns', 'Returns', 'الاسترجاع');
    makePage('warranty', 'Warranty', 'الضمان');
    makePage('careers', 'Careers', 'الوظائف');
    makePage('terms', 'Terms and conditions', 'الشروط والأحكام');
    makePage('privacy', 'Privacy policy', 'سياسة الخصوصية');

    foreach (['delivery', 'returns', 'warranty', 'careers', 'terms-and-conditions', 'privacy-policy'] as $slug) {
        $this->get("/en/p/{$slug}")->assertOk();
    }
});

// ─────────────────────── AR pages ───────────────────────

it('renders the delivery page in AR via Arabic slug', function () {
    makePage('delivery', 'Delivery', 'التوصيل');

    $this->get('/ar/p/'.rawurlencode('التوصيل'))
        ->assertOk()
        ->assertSee('التوصيل')
        ->assertSee('Body AR.');
});

// ─────────────────────── Side nav ───────────────────────

it('shows all footer pages in the side nav', function () {
    makePage('delivery', 'Delivery', 'التوصيل');
    makePage('returns', 'Returns', 'الاسترجاع');
    makePage('warranty', 'Warranty', 'الضمان');

    $this->get('/en/p/delivery')
        ->assertOk()
        ->assertSee('Returns')
        ->assertSee('Warranty');
});

it('does not show inactive pages', function () {
    $p = makePage('delivery', 'Delivery', 'التوصيل');
    Page::create([
        'key' => 'hidden',
        'title' => ['en' => 'Hidden', 'ar' => 'مخفي'],
        'slug' => ['en' => 'hidden', 'ar' => 'مخفي'],
        'lede' => null,
        'body' => null,
        'show_in_footer' => true,
        'sort_order' => 99,
        'is_active' => false,
    ]);

    $this->get('/en/p/delivery')->assertOk()->assertDontSee('Hidden');
});

// ─────────────────────── 404 ───────────────────────

it('returns 404 for an unknown slug', function () {
    $this->get('/en/p/not-a-real-page')->assertNotFound();
});
