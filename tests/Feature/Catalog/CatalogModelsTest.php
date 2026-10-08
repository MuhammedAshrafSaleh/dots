<?php

use App\Enums\AttributeDisplay;
use App\Enums\ProductRelationType;
use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\QueryException;

/**
 * Build a minimal product with its brand + leaf category.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeProduct(array $attributes = []): Product
{
    $brand = Brand::create(['name' => 'Philips', 'slug' => 'philips']);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'سبوت', 'en' => 'Spots'],
        'slug' => ['ar' => 'سبوت', 'en' => 'spots'],
    ]);

    return Product::create(array_merge([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'كشاف', 'en' => 'Downlight'],
        'slug' => ['ar' => 'كشاف', 'en' => 'downlight'],
        'sku' => 'DL-001',
    ], $attributes));
}

it('stores translatable name per locale', function () {
    $product = makeProduct();

    expect($product->getTranslation('name', 'ar'))->toBe('كشاف')
        ->and($product->getTranslation('name', 'en'))->toBe('Downlight');
});

it('populates the generated per-locale slug columns from the JSON', function () {
    $product = makeProduct();

    expect($product->fresh()->slug_ar)->toBe('كشاف')
        ->and($product->fresh()->slug_en)->toBe('downlight');
});

it('resolves route binding by the active locale slug and falls back to the other', function () {
    $product = makeProduct();

    app()->setLocale('en');
    expect((new Product)->resolveRouteBinding('downlight')?->is($product))->toBeTrue();

    app()->setLocale('ar');
    expect((new Product)->resolveRouteBinding('كشاف')?->is($product))->toBeTrue();
    // Falls back to the other locale so a shared/legacy link still resolves.
    expect((new Product)->resolveRouteBinding('downlight')?->is($product))->toBeTrue();
});

it('enforces per-locale slug uniqueness', function () {
    makeProduct();

    Product::create([
        'brand_id' => Brand::first()->id,
        'category_id' => Category::first()->id,
        'name' => ['ar' => 'آخر', 'en' => 'Other'],
        'slug' => ['ar' => 'آخر', 'en' => 'downlight'], // duplicate EN slug
        'sku' => 'DL-002',
    ]);
})->throws(QueryException::class);

it('relates a product to its brand, category, variants and specs', function () {
    $product = makeProduct();
    $variant = $product->variants()->create(['sku' => 'DL-001-A', 'price' => 100, 'is_default' => true]);
    $product->specs()->create(['name' => ['ar' => 'الفيض', 'en' => 'Flux'], 'value' => ['ar' => '2450', 'en' => '2450 lm']]);

    expect($product->brand)->toBeInstanceOf(Brand::class)
        ->and($product->category->level)->toBe(3)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->specs->first())->toBeInstanceOf(ProductSpec::class)
        ->and($variant->product->is($product))->toBeTrue();
});

it('links variants to attribute values through the pivot', function () {
    $product = makeProduct();
    $variant = $product->variants()->create(['sku' => 'DL-001-A']);

    $attribute = Attribute::create(['code' => 'cct', 'name' => ['ar' => 'درجة', 'en' => 'CCT'], 'display' => AttributeDisplay::Swatch]);
    $value = $attribute->values()->create(['value' => '3000', 'label' => ['ar' => 'دافئ', 'en' => 'Warm']]);

    $variant->attributeValues()->attach($value);

    expect($variant->attributeValues->first()->is($value))->toBeTrue()
        ->and($value->variants->first()->is($variant))->toBeTrue()
        ->and($attribute->display)->toBe(AttributeDisplay::Swatch);
});

it('separates related products from cart suggestions by type', function () {
    $product = makeProduct();
    $together = makeProductWithSku('T-1');
    $suggestion = makeProductWithSku('S-1');

    $product->relatedProducts()->attach($together, ['type' => ProductRelationType::Together->value]);
    $product->cartSuggestions()->attach($suggestion, ['type' => ProductRelationType::CartSuggestion->value]);

    expect($product->relatedProducts()->pluck('products.id')->all())->toBe([$together->id])
        ->and($product->cartSuggestions()->pluck('products.id')->all())->toBe([$suggestion->id]);
});

it('attaches featured products to a category', function () {
    $product = makeProduct();
    $category = $product->category;

    $category->featuredProducts()->attach($product, ['sort_order' => 1]);

    expect($category->featuredProducts->first()->is($product))->toBeTrue();
});

it('casts enums and scopes published products', function () {
    $draft = makeProduct(['status' => ProductStatus::Draft, 'stock_status' => StockStatus::In]);
    $live = makeProductWithSku('LIVE-1');
    $live->update(['status' => ProductStatus::Active, 'published_at' => now()->subDay()]);
    $future = makeProductWithSku('FUT-1');
    $future->update(['status' => ProductStatus::Active, 'published_at' => now()->addDay()]);

    expect($draft->status)->toBe(ProductStatus::Draft)
        ->and($draft->stock_status)->toBe(StockStatus::In)
        ->and(Product::active()->pluck('id'))->toContain($live->id, $future->id)
        ->and(Product::published()->pluck('id')->all())->toBe([$live->id]);
});

/** A second/third product reusing the same brand + category. */
function makeProductWithSku(string $sku): Product
{
    return Product::create([
        'brand_id' => Brand::first()->id,
        'category_id' => Category::first()->id,
        'name' => ['ar' => $sku, 'en' => $sku],
        'slug' => ['ar' => $sku, 'en' => $sku],
        'sku' => $sku,
    ]);
}
