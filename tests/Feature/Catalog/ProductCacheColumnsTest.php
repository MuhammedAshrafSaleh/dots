<?php

use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CacheTags;
use Illuminate\Support\Facades\Cache;

function cacheProduct(array $attributes = []): Product
{
    $brand = Brand::create(['name' => 'Philips', 'slug' => 'philips-'.uniqid()]);
    $category = Category::create(['level' => 3, 'name' => ['ar' => 'ق', 'en' => 'C'], 'slug' => ['ar' => 'ق'.uniqid(), 'en' => 'c'.uniqid()]]);

    return Product::create(array_merge([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'كشاف', 'en' => 'Downlight'],
        'slug' => ['ar' => 'كشاف'.uniqid(), 'en' => 'dl'.uniqid()],
        'sku' => 'DL-'.uniqid(),
    ], $attributes));
}

it('caches min_price and max_compare_at from active variants', function () {
    $product = cacheProduct();
    $product->variants()->create(['sku' => 'A', 'price' => 100, 'compare_at_price' => 120, 'stock_qty' => 10]);
    $product->variants()->create(['sku' => 'B', 'price' => 80, 'compare_at_price' => 90, 'stock_qty' => 10]);
    $product->variants()->create(['sku' => 'C', 'price' => 50, 'is_active' => false]); // ignored

    $product->refresh();

    expect($product->min_price)->toBe('80.00')
        ->and($product->max_compare_at)->toBe('120.00');
});

it('derives stock_status: in, low and on-order', function () {
    // Threshold default is 5.
    $inStock = cacheProduct();
    $inStock->variants()->create(['sku' => 'IN', 'price' => 10, 'stock_qty' => 10]);
    expect($inStock->refresh()->stock_status)->toBe(StockStatus::In);

    $low = cacheProduct();
    $low->variants()->create(['sku' => 'LOW', 'price' => 10, 'stock_qty' => 3]);
    expect($low->refresh()->stock_status)->toBe(StockStatus::Low);

    $out = cacheProduct();
    $out->variants()->create(['sku' => 'OUT', 'price' => 10, 'stock_qty' => 0]);
    expect($out->refresh()->stock_status)->toBe(StockStatus::Order);
});

it('is always on-order for a price-on-request product', function () {
    $product = cacheProduct(['price_on_request' => true]);
    $product->variants()->create(['sku' => 'POR', 'price' => null, 'stock_qty' => 100]);

    expect($product->refresh()->stock_status)->toBe(StockStatus::Order)
        ->and($product->min_price)->toBeNull();
});

it('recomputes columns when a variant price or stock changes or is deleted', function () {
    $product = cacheProduct();
    $a = $product->variants()->create(['sku' => 'A', 'price' => 100, 'stock_qty' => 10]);
    $b = $product->variants()->create(['sku' => 'B', 'price' => 60, 'stock_qty' => 10]);

    expect($product->refresh()->min_price)->toBe('60.00');

    $b->update(['price' => 200]);
    expect($product->refresh()->min_price)->toBe('100.00');

    $a->update(['stock_qty' => 0]);
    $b->update(['stock_qty' => 0]);
    expect($product->refresh()->stock_status)->toBe(StockStatus::Order);

    $a->delete();
    $b->delete();
    expect($product->refresh()->min_price)->toBeNull()
        ->and($product->stock_status)->toBe(StockStatus::Order);
});

it('builds search_text from both name locales, all SKUs and the brand', function () {
    $product = cacheProduct();
    $product->variants()->create(['sku' => 'DL-001-A', 'price' => 10, 'stock_qty' => 1]);

    $text = $product->refresh()->search_text;

    expect($text)->toContain('كشاف')
        ->and($text)->toContain('Downlight')
        ->and($text)->toContain('DL-001-A')
        ->and($text)->toContain('Philips');
});

it('flushes tagged caches when a product is saved', function () {
    Cache::tags([CacheTags::LISTINGS])->put('page-1', 'cached', 60);
    expect(Cache::tags([CacheTags::LISTINGS])->get('page-1'))->toBe('cached');

    cacheProduct(); // triggers ProductObserver::saved → flush

    expect(Cache::tags([CacheTags::LISTINGS])->get('page-1'))->toBeNull();
});
