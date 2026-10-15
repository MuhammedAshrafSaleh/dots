<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;

beforeEach(function () {
    $this->withoutVite();
});

// ──────────────────────── Helpers ────────────────────────

function catMakeCategory(array $attrs = []): Category
{
    $name = $attrs['name'] ?? ['en' => 'Indoor Lighting', 'ar' => 'إضاءة داخلية'];
    $slug = $attrs['slug'] ?? ['en' => 'indoor-lighting', 'ar' => 'إضاءة-داخلية'];

    return Category::create(array_merge([
        'parent_id' => null,
        'level' => 1,
        'name' => $name,
        'slug' => $slug,
        'sort_order' => 0,
        'is_active' => true,
    ], $attrs));
}

function catMakeBrand(string $name, string $slug): Brand
{
    return Brand::create([
        'name' => $name,
        'slug' => $slug,
        'origin_country' => ['en' => 'France', 'ar' => 'فرنسا'],
        'description' => ['en' => 'Test brand.', 'ar' => 'علامة تجريبية.'],
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function catMakeProduct(Category $cat, Brand $brand, string $skuSuffix = ''): Product
{
    return Product::create([
        'name' => ['en' => "Product $skuSuffix", 'ar' => "منتج $skuSuffix"],
        'slug' => ['en' => "product-$skuSuffix", 'ar' => "منتج-$skuSuffix"],
        'sku' => "SKU-$skuSuffix",
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'status' => ProductStatus::Active,
    ]);
}

// ──────────────────────── Basic rendering ────────────────────────

it('renders the category page in EN', function () {
    catMakeCategory(['name' => ['en' => 'Downlights', 'ar' => 'سبوتات'], 'slug' => ['en' => 'downlights', 'ar' => 'سبوتات']]);

    $this->get('/en/c/downlights')->assertOk()->assertSee('Downlights');
});

it('renders the category page in AR', function () {
    catMakeCategory(['name' => ['en' => 'Pendants', 'ar' => 'معلقات'], 'slug' => ['en' => 'pendants', 'ar' => 'معلقات']]);

    $this->get('/ar/c/'.urlencode('معلقات'))->assertOk()->assertSee('معلقات');
});

it('returns 404 for a non-existent slug', function () {
    $this->get('/en/c/no-such-category')->assertNotFound();
});

it('returns 404 for an inactive category', function () {
    catMakeCategory(['slug' => ['en' => 'hidden', 'ar' => 'مخفي'], 'is_active' => false]);

    $this->get('/en/c/hidden')->assertNotFound();
});

// ──────────────────────── Sub-category tiles ────────────────────────

it('shows sub-category tiles for children', function () {
    $parent = catMakeCategory(['name' => ['en' => 'Indoor', 'ar' => 'داخلي'], 'slug' => ['en' => 'indoor', 'ar' => 'داخلي']]);
    catMakeCategory([
        'parent_id' => $parent->id,
        'name' => ['en' => 'Pendants', 'ar' => 'معلقات'],
        'slug' => ['en' => 'pendants', 'ar' => 'معلقات'],
    ]);

    $this->get('/en/c/indoor')->assertOk()->assertSee('Pendants');
});

it('does not show inactive sub-categories', function () {
    $parent = catMakeCategory(['name' => ['en' => 'Indoor', 'ar' => 'داخلي'], 'slug' => ['en' => 'indoor2', 'ar' => 'داخلي2']]);
    catMakeCategory([
        'parent_id' => $parent->id,
        'name' => ['en' => 'Hidden Sub', 'ar' => 'فرعي مخفي'],
        'slug' => ['en' => 'hidden-sub', 'ar' => 'فرعي-مخفي'],
        'is_active' => false,
    ]);

    $this->get('/en/c/indoor2')->assertOk()->assertDontSee('Hidden Sub');
});

it('shows the product count on sub-category tiles', function () {
    $parent = catMakeCategory(['name' => ['en' => 'Indoor', 'ar' => 'داخلي'], 'slug' => ['en' => 'indoor3', 'ar' => 'داخلي3']]);
    $child = catMakeCategory([
        'parent_id' => $parent->id,
        'name' => ['en' => 'Spots', 'ar' => 'سبوتات'],
        'slug' => ['en' => 'spots', 'ar' => 'سبوتات'],
    ]);
    $brand = catMakeBrand('Philips', 'philips-cnt');
    catMakeProduct($child, $brand, 'A1');
    catMakeProduct($child, $brand, 'A2');

    $this->get('/en/c/indoor3')->assertOk()->assertSee('2');
});

// ──────────────────────── Top picks ────────────────────────

it('shows featured products in top picks', function () {
    $cat = catMakeCategory(['name' => ['en' => 'Smart Home', 'ar' => 'المنزل الذكي'], 'slug' => ['en' => 'smart-home', 'ar' => 'المنزل-الذكي']]);
    $brand = catMakeBrand('ABB', 'abb-tp');
    $prod = catMakeProduct($cat, $brand, 'P1');
    $cat->featuredProducts()->attach($prod->id, ['sort_order' => 1]);

    $this->get('/en/c/smart-home')->assertOk()->assertSee('Product P1');
});

it('hides top picks section when no featured products', function () {
    catMakeCategory(['name' => ['en' => 'Outdoor', 'ar' => 'خارجية'], 'slug' => ['en' => 'outdoor', 'ar' => 'خارجية']]);

    $this->get('/en/c/outdoor')
        ->assertOk()
        ->assertDontSee(__('ui.cat_top_picks_title'));
});

// ──────────────────────── Buying guide + FAQ ────────────────────────

it('shows FAQ accordion items', function () {
    $cat = catMakeCategory(['name' => ['en' => 'Pendants', 'ar' => 'معلقات'], 'slug' => ['en' => 'pend', 'ar' => 'معلقات-س'], 'buying_guide_title' => ['en' => 'Choosing the right pendant', 'ar' => 'اختيار المعلقة']]);
    Faq::create([
        'faqable_type' => Category::class,
        'faqable_id' => $cat->id,
        'question' => ['en' => 'What size fits my table?', 'ar' => 'ما المقاس المناسب؟'],
        'answer' => ['en' => 'Half the table width.', 'ar' => 'نصف عرض الطاولة.'],
        'sort_order' => 1,
    ]);

    $this->get('/en/c/pend')
        ->assertOk()
        ->assertSee('Choosing the right pendant')
        ->assertSee('What size fits my table?');
});

it('shows brand chips for brands with products in category', function () {
    $cat = catMakeCategory(['name' => ['en' => 'Strips', 'ar' => 'شرائط'], 'slug' => ['en' => 'strips', 'ar' => 'شرائط']]);
    $brand = catMakeBrand('Legrand', 'legrand-cat');
    catMakeProduct($cat, $brand, 'S1');

    $this->get('/en/c/strips')->assertOk()->assertSee('Legrand');
});

it('hides buying guide section when no guide data', function () {
    catMakeCategory(['name' => ['en' => 'Sockets', 'ar' => 'برايز'], 'slug' => ['en' => 'sockets', 'ar' => 'برايز']]);

    $this->get('/en/c/sockets')
        ->assertOk()
        ->assertDontSee(__('ui.cat_buying_guide'));
});

// ──────────────────────── Total count in hero ────────────────────────

it('shows the total product count in the shop-all button', function () {
    $cat = catMakeCategory(['name' => ['en' => 'Downlights', 'ar' => 'داونلايت'], 'slug' => ['en' => 'dl', 'ar' => 'داونلايت']]);
    $brand = catMakeBrand('Osram', 'osram-dl');
    catMakeProduct($cat, $brand, 'D1');
    catMakeProduct($cat, $brand, 'D2');
    catMakeProduct($cat, $brand, 'D3');

    $this->get('/en/c/dl')
        ->assertOk()
        ->assertSee('3');
});

it('includes child category products in the hero count', function () {
    $parent = catMakeCategory(['name' => ['en' => 'Indoor', 'ar' => 'داخلي'], 'slug' => ['en' => 'indoor-cnt', 'ar' => 'داخلي-cnt']]);
    $child = catMakeCategory(['parent_id' => $parent->id, 'name' => ['en' => 'Subs', 'ar' => 'فرعي'], 'slug' => ['en' => 'subs-cnt', 'ar' => 'فرعي-cnt']]);
    $brand = catMakeBrand('Delta', 'delta-cnt');
    catMakeProduct($parent, $brand, 'I1');
    catMakeProduct($child, $brand, 'I2');
    catMakeProduct($child, $brand, 'I3');

    $this->get('/en/c/indoor-cnt')
        ->assertOk()
        ->assertSee('3');
});
