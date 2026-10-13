<?php

use App\Models\Category;

/**
 * Real-browser E2E (Pest 4 + Playwright) for the T5.1 global layout: the header
 * (utility strip, nav, mega menu), language switch, and footer render and work
 * in both locales over a real HTTP server — catching render crashes and JS
 * errors the Feature tests can't see.
 */
function seedMegaTree(): void
{
    $root = Category::create([
        'level' => 1, 'icon' => 'lamp-ceiling', 'is_active' => true, 'show_in_footer' => true, 'sort_order' => 1,
        'name' => ['ar' => 'إضاءة داخلية', 'en' => 'Indoor Lighting'],
        'slug' => ['ar' => 'اضاءه-داخليه', 'en' => 'indoor-lighting'],
    ]);
    $group = Category::create([
        'level' => 2, 'parent_id' => $root->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'أسقف', 'en' => 'Ceiling'],
        'slug' => ['ar' => 'اسقف', 'en' => 'ceiling'],
    ]);
    Category::create([
        'level' => 3, 'parent_id' => $group->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'معلقات', 'en' => 'Pendants'],
        'slug' => ['ar' => 'معلقات', 'en' => 'pendants'],
    ]);
}

it('renders the global header and footer in English', function () {
    seedMegaTree();

    visit('/en')
        ->assertSee('Products')
        ->assertSee('Brands')
        ->assertSee('Track order')
        ->assertSee('Shop')          // footer column header
        ->assertSee('All brands')    // footer link
        ->assertNoJavaScriptErrors();
});

it('opens the category mega menu', function () {
    seedMegaTree();

    visit('/en')
        ->click('Categories')
        ->assertSee('Indoor Lighting')
        ->assertSee('Pendants')
        ->assertNoJavaScriptErrors();
});

it('switches locale from the header language switch', function () {
    seedMegaTree();

    visit('/en')
        ->assertSee('Products')
        ->click('عربي')
        ->assertPathIs('/ar')
        ->assertSee('المنتجات')
        ->assertNoJavaScriptErrors();
});
