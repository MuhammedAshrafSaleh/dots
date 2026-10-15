<?php

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Livewire\Shop\ProductListing;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

// ──────────────────────── Helpers ────────────────────────

function pcMakeCategory(): Category
{
    static $n = 0;
    $n++;

    return Category::create([
        'parent_id' => null,
        'level' => 1,
        'name' => ['en' => "Cat {$n}", 'ar' => "قسم {$n}"],
        'slug' => ['en' => "pc-cat-{$n}", 'ar' => "قسم-pc-{$n}"],
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function pcMakeBrand(): Brand
{
    static $n = 0;
    $n++;

    return Brand::create([
        'name' => "Brand {$n}",
        'slug' => "pc-brand-{$n}",
        'origin_country' => ['en' => 'Egypt', 'ar' => 'مصر'],
        'description' => ['en' => 'Test.', 'ar' => 'اختبار.'],
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function pcMakeProduct(array $attrs = []): Product
{
    static $n = 0;
    $n++;

    $cat = pcMakeCategory();
    $brand = pcMakeBrand();

    $product = new Product(array_merge([
        'name' => ['en' => "Fixture {$n}", 'ar' => "تجهيز {$n}"],
        'slug' => ['en' => "fixture-pc-{$n}", 'ar' => "تجهيز-pc-{$n}"],
        'sku' => "PC-{$n}",
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'status' => ProductStatus::Active,
        'min_price' => 800,
        'published_at' => now(),
        'sort_weight' => 0,
        'stock_status' => StockStatus::In->value,
    ], $attrs));

    $product->saveQuietly();

    return $product;
}

// ──────────────────────── POR card: quote as primary ────────────────────────

it('price-on-request card shows full-width quote button as primary action', function () {
    pcMakeProduct(['price_on_request' => true, 'min_price' => null]);

    Livewire::test(ProductListing::class)
        ->assertSee(__('ui.request_quote'))
        ->assertDontSee(__('ui.pcard_add_to_cart'));
});

it('price-on-request card shows price_on_request badge', function () {
    pcMakeProduct(['price_on_request' => true, 'min_price' => null]);

    Livewire::test(ProductListing::class)
        ->assertSee(__('ui.price_on_request'));
});

// ──────────────────────── Regular priced card ────────────────────────

it('regular priced card shows add-to-cart button', function () {
    pcMakeProduct(['min_price' => 1200, 'price_on_request' => false]);

    Livewire::test(ProductListing::class)
        ->assertSee(__('ui.pcard_add_to_cart'));
});

it('regular priced card shows price in EGP', function () {
    pcMakeProduct(['min_price' => 1500.00, 'price_on_request' => false]);

    Livewire::test(ProductListing::class)
        ->assertSee('1,500');
});

// ──────────────────────── Sale badge ────────────────────────

it('auto-computes sale badge when compare_at price is higher', function () {
    // 750 of 1000 = 25% off
    pcMakeProduct(['min_price' => 750, 'max_compare_at' => 1000, 'price_on_request' => false]);

    Livewire::test(ProductListing::class)
        ->assertSee('25%');
});

it('does not show sale badge when there is no compare_at price', function () {
    pcMakeProduct(['min_price' => 800, 'max_compare_at' => null, 'price_on_request' => false]);

    Livewire::test(ProductListing::class)
        ->assertDontSee(__('ui.pcard_sale_off', ['pct' => 0]));
});

// ──────────────────────── Stock label ────────────────────────

it('renders in-stock label for StockStatus::In', function () {
    pcMakeProduct(['stock_status' => StockStatus::In->value]);

    Livewire::test(ProductListing::class)
        ->assertSee(__('ui.pcard_in_stock'));
});

it('renders low-stock label for StockStatus::Low', function () {
    pcMakeProduct(['stock_status' => StockStatus::Low->value]);

    Livewire::test(ProductListing::class)
        ->assertSee(__('ui.pcard_low_stock'));
});

it('renders on-order label for StockStatus::Order', function () {
    pcMakeProduct(['stock_status' => StockStatus::Order->value]);

    Livewire::test(ProductListing::class)
        ->assertSee(__('ui.pcard_on_order'));
});

// ──────────────────────── Wishlist prompt ────────────────────────

it('renders wishlist heart button on card', function () {
    pcMakeProduct();

    Livewire::test(ProductListing::class)
        ->assertSee('d-pcard__fav', false);
});

it('renders wishlist prompt for logged-out visitors', function () {
    pcMakeProduct();

    Livewire::test(ProductListing::class)
        ->assertSee('d-favtip', false)
        ->assertSee(__('ui.pcard_wishlist_title'));
});
