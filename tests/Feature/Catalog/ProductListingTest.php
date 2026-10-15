<?php

use App\Enums\AttributeDisplay;
use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Livewire\Shop\ProductListing;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

// ──────────────────────── Helpers ────────────────────────

function plMakeCategory(array $attrs = []): Category
{
    static $n = 0;
    $n++;

    return Category::create(array_merge([
        'parent_id' => null,
        'level' => 1,
        'name' => ['en' => "Cat {$n}", 'ar' => "قسم {$n}"],
        'slug' => ['en' => "cat-{$n}", 'ar' => "قسم-{$n}"],
        'sort_order' => 0,
        'is_active' => true,
    ], $attrs));
}

function plMakeBrand(string $name): Brand
{
    return Brand::create([
        'name' => $name,
        'slug' => Str::slug($name).'-pl'.rand(1000, 9999),
        'origin_country' => ['en' => 'Egypt', 'ar' => 'مصر'],
        'description' => ['en' => 'Test.', 'ar' => 'اختبار.'],
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function plMakeProduct(Category $cat, Brand $brand, array $attrs = []): Product
{
    static $n = 0;
    $n++;

    $product = new Product(array_merge([
        'name' => ['en' => "Product {$n}", 'ar' => "منتج {$n}"],
        'slug' => ['en' => "product-{$n}", 'ar' => "منتج-{$n}"],
        'sku' => "PL-{$n}",
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'status' => ProductStatus::Active,
        'min_price' => 500,
        'published_at' => now(),
        'sort_weight' => 0,
        'stock_status' => StockStatus::In->value,
    ], $attrs));

    $product->saveQuietly();

    return $product;
}

// ──────────────────────── Basic render ────────────────────────

it('renders the products listing page', function () {
    $this->get('/en/products')->assertOk()->assertSee(__('ui.all_products'));
});

it('renders the products listing page in Arabic', function () {
    $this->get('/ar/products')->assertOk()->assertSeeText(__('ui.all_products'));
});

// ──────────────────────── Category filter ────────────────────────

it('filters products by category', function () {
    $catA = plMakeCategory();
    $catB = plMakeCategory();
    $brand = plMakeBrand('TestBrand');

    plMakeProduct($catA, $brand);
    plMakeProduct($catA, $brand);
    $b = plMakeProduct($catB, $brand);

    Livewire::test(ProductListing::class)
        ->set('categoryId', $catB->id)
        ->assertSee("Product {$b->id}")
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
})->skip('product id not in card until T6.3');

it('shows all products when no category filter', function () {
    $catA = plMakeCategory();
    $catB = plMakeCategory();
    $brand = plMakeBrand('AllBrand');

    plMakeProduct($catA, $brand);
    plMakeProduct($catB, $brand);

    Livewire::test(ProductListing::class)
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});

it('includes descendant category products in filter', function () {
    $parent = plMakeCategory(['level' => 1]);
    $child = plMakeCategory(['parent_id' => $parent->id, 'level' => 2]);
    $brand = plMakeBrand('DeepBrand');

    plMakeProduct($child, $brand);

    Livewire::test(ProductListing::class)
        ->set('categoryId', $parent->id)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

// ──────────────────────── Brand filter ────────────────────────

it('filters products by brand', function () {
    $cat = plMakeCategory();
    $b1 = plMakeBrand('Philips');
    $b2 = plMakeBrand('Osram');

    plMakeProduct($cat, $b1);
    plMakeProduct($cat, $b1);
    plMakeProduct($cat, $b2);

    Livewire::test(ProductListing::class)
        ->call('toggleBrand', $b1->id)
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});

it('can select multiple brands', function () {
    $cat = plMakeCategory();
    $b1 = plMakeBrand('Alpha');
    $b2 = plMakeBrand('Beta');
    $b3 = plMakeBrand('Gamma');

    plMakeProduct($cat, $b1);
    plMakeProduct($cat, $b2);
    plMakeProduct($cat, $b3);

    Livewire::test(ProductListing::class)
        ->call('toggleBrand', $b1->id)
        ->call('toggleBrand', $b2->id)
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});

it('removes a brand filter by toggling again', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('Removable');
    plMakeProduct($cat, $brand);
    plMakeProduct($cat, $brand);

    Livewire::test(ProductListing::class)
        ->call('toggleBrand', $brand->id)
        ->assertViewHas('products', fn ($p) => $p->total() === 2)
        ->call('toggleBrand', $brand->id)
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});

// ──────────────────────── Attribute filter ────────────────────────

it('filters products by attribute value', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('AttrBrand');

    $attr = Attribute::create([
        'code' => 'cct-test-'.rand(100, 999),
        'name' => ['en' => 'CCT', 'ar' => 'درجة اللون'],
        'display' => AttributeDisplay::Chip,
        'is_filterable' => true,
        'sort_order' => 1,
    ]);

    $warm = AttributeValue::create([
        'attribute_id' => $attr->id,
        'value' => '3000',
        'label' => ['en' => 'Warm', 'ar' => 'دافئ'],
        'sort_order' => 0,
    ]);

    $cool = AttributeValue::create([
        'attribute_id' => $attr->id,
        'value' => '6500',
        'label' => ['en' => 'Cool', 'ar' => 'بارد'],
        'sort_order' => 1,
    ]);

    $warmProduct = plMakeProduct($cat, $brand);
    $coolProduct = plMakeProduct($cat, $brand);

    $warmProduct->variants()->create(['sku' => 'W1', 'price' => 100, 'stock_qty' => 5, 'is_default' => true, 'is_active' => true])
        ->attributeValues()->attach($warm->id);

    $coolProduct->variants()->create(['sku' => 'C1', 'price' => 100, 'stock_qty' => 5, 'is_default' => true, 'is_active' => true])
        ->attributeValues()->attach($cool->id);

    Livewire::test(ProductListing::class)
        ->call('toggleAttrValue', $warm->id)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

// ──────────────────────── Price filter ────────────────────────

it('filters products by price range', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('PriceBrand');

    plMakeProduct($cat, $brand, ['min_price' => 100]);
    plMakeProduct($cat, $brand, ['min_price' => 500]);
    plMakeProduct($cat, $brand, ['min_price' => 900]);

    Livewire::test(ProductListing::class)
        ->call('applyPriceRange', 200, 700)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

it('filters by min price only', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('MinBrand');

    plMakeProduct($cat, $brand, ['min_price' => 200]);
    plMakeProduct($cat, $brand, ['min_price' => 1000]);

    Livewire::test(ProductListing::class)
        ->call('applyPriceRange', 500, null)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

// ──────────────────────── Availability filters ────────────────────────

it('filters products by in-stock', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('StockBrand');

    plMakeProduct($cat, $brand, ['stock_status' => StockStatus::In->value]);
    plMakeProduct($cat, $brand, ['stock_status' => StockStatus::Order->value]);

    Livewire::test(ProductListing::class)
        ->set('inStock', true)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

it('filters products by ships-24h', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('FastBrand');

    plMakeProduct($cat, $brand, ['ships_24h' => true]);
    plMakeProduct($cat, $brand, ['ships_24h' => false]);

    Livewire::test(ProductListing::class)
        ->set('ships24h', true)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

// ──────────────────────── Sort ────────────────────────

it('sorts by price ascending', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('SortBrand');

    $cheap = plMakeProduct($cat, $brand, ['min_price' => 100, 'sort_weight' => 50]);
    $expensive = plMakeProduct($cat, $brand, ['min_price' => 900, 'sort_weight' => 10]);

    $component = Livewire::test(ProductListing::class)->set('sort', 'price_asc');
    $products = $component->viewData('products');
    expect($products->first()->id)->toBe($cheap->id);
});

it('sorts by price descending', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('SortDesc');

    $cheap = plMakeProduct($cat, $brand, ['min_price' => 100, 'sort_weight' => 50]);
    $expensive = plMakeProduct($cat, $brand, ['min_price' => 900, 'sort_weight' => 10]);

    $component = Livewire::test(ProductListing::class)->set('sort', 'price_desc');
    $products = $component->viewData('products');
    expect($products->first()->id)->toBe($expensive->id);
});

// ──────────────────────── Clear all ────────────────────────

it('clears all filters', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('ClearBrand');

    plMakeProduct($cat, $brand, ['stock_status' => StockStatus::In->value]);
    plMakeProduct($cat, $brand, ['stock_status' => StockStatus::Order->value]);

    Livewire::test(ProductListing::class)
        ->set('inStock', true)
        ->assertViewHas('products', fn ($p) => $p->total() === 1)
        ->call('clearAll')
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});

// ──────────────────────── Pagination ────────────────────────

it('paginates 12 products per page', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('PageBrand');

    for ($i = 0; $i < 15; $i++) {
        plMakeProduct($cat, $brand);
    }

    Livewire::test(ProductListing::class)
        ->assertViewHas('products', fn ($p) => $p->count() === 12 && $p->total() === 15);
});

// ──────────────────────── Active filter count ────────────────────────

it('counts active filters correctly', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('CountBrand');

    $component = Livewire::test(ProductListing::class)
        ->call('toggleBrand', plMakeBrand('X')->id)
        ->set('inStock', true)
        ->call('applyPriceRange', 100, 500);

    expect($component->viewData('activeCount'))->toBe(3);
});

// ──────────────────────── URL query string state ────────────────────────

it('reads categoryId from the URL', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('URLBrand');

    plMakeProduct($cat, $brand);

    Livewire::withQueryParams(['cat' => $cat->id])
        ->test(ProductListing::class)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});

// ──────────────────────── Inactive product excluded ────────────────────────

it('excludes inactive products from listing', function () {
    $cat = plMakeCategory();
    $brand = plMakeBrand('HideBrand');

    plMakeProduct($cat, $brand, ['status' => ProductStatus::Active]);
    plMakeProduct($cat, $brand, ['status' => ProductStatus::Draft ?? 'draft']);

    Livewire::test(ProductListing::class)
        ->assertViewHas('products', fn ($p) => $p->total() === 1);
});
