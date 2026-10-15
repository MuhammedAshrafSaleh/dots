<?php

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Livewire\Shop\ProductListing;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ArabicNorm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

// ──────────────────────── Helpers ────────────────────────

function srMakeCat(string $nameEn = 'Test', string $slug = ''): Category
{
    static $n = 0;
    $n++;
    $slug = $slug ?: 'sr-cat-'.$n;

    return Category::create([
        'parent_id' => null,
        'level' => 1,
        'name' => ['en' => $nameEn, 'ar' => $nameEn],
        'slug' => ['en' => $slug, 'ar' => $slug.'-ar'],
        'sort_order' => 0,
        'is_active' => true,
    ]);
}

function srMakeBrand(string $name): Brand
{
    return Brand::create([
        'name' => $name,
        'slug' => Str::slug($name).'-sr'.rand(100, 999),
        'origin_country' => ['en' => 'Egypt', 'ar' => 'مصر'],
        'description' => ['en' => 'Test.', 'ar' => 'اختبار.'],
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function srMakeProduct(Category $cat, Brand $brand, string $searchText, array $attrs = []): Product
{
    static $n = 0;
    $n++;

    $product = new Product(array_merge([
        'name' => ['en' => "SR Product {$n}", 'ar' => "منتج {$n}"],
        'slug' => ['en' => "sr-product-{$n}", 'ar' => "sr-منتج-{$n}"],
        'sku' => "SR-{$n}",
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'status' => ProductStatus::Active,
        'min_price' => 100,
        'published_at' => now(),
        'sort_weight' => 0,
        'stock_status' => StockStatus::In->value,
        'search_text' => $searchText,
    ], $attrs));

    $product->saveQuietly();

    return $product;
}

// ──────────────────────── ArabicNorm unit tests ────────────────────────

it('strips harakat', function () {
    expect(ArabicNorm::normalize('مُحَمَّد'))->toBe('محمد');
});

it('normalises alef variants to bare alef', function () {
    expect(ArabicNorm::normalize('أإآٱ'))->toBe('اااا');
});

it('normalises alef maqsura to yaa', function () {
    expect(ArabicNorm::normalize('مصطفى'))->toBe('مصطفي');
});

it('normalises taa marbuta to haa', function () {
    expect(ArabicNorm::normalize('إضاءة'))->toBe('اضاءه');
});

it('normalises alef-hamza and taa-marbuta together', function () {
    // "إضاءة" → strip alef-hamza "إ"→"ا", taa-marbuta "ة"→"ه"
    expect(ArabicNorm::normalize('إضاءة'))->toBe('اضاءه')
        ->and(ArabicNorm::normalize('اضاءة'))->toBe('اضاءه'); // already-bare alef: same result
});

it('sanitizeQuery removes boolean FT operators', function () {
    expect(ArabicNorm::sanitizeQuery('+foo-bar'))->toBe(' foo bar');
});

// ──────────────────────── Search page rendering ────────────────────────

it('renders the search page in English', function () {
    $this->get('/en/search')->assertOk()->assertSee(__('ui.search_results'));
});

it('renders the search page in Arabic', function () {
    $this->get('/ar/search')->assertOk()->assertSeeText(__('ui.search_results'));
});

it('renders search page with q param without error', function () {
    $this->get('/en/search?q=led')->assertOk();
});

// ──────────────────────── FULLTEXT clause emitted ────────────────────────
// Note: InnoDB FTS auxiliary tables are only visible for committed rows; Pest's
// RefreshDatabase wraps each test in a rolled-back transaction, so we cannot
// assert on result counts here. Instead we verify via query log that the
// MATCH…AGAINST WHERE clause is emitted correctly.

it('emits a MATCH(search_text) clause when q is set', function () {
    $cat = srMakeCat();
    $brand = srMakeBrand('QueryLogBrand');
    srMakeProduct($cat, $brand, 'LED Downlight');

    DB::enableQueryLog();
    Livewire::withQueryParams(['q' => 'Downlight'])->test(ProductListing::class);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $hasFT = collect($queries)->contains(fn ($q) => str_contains(strtolower($q['query']), 'match('));
    expect($hasFT)->toBeTrue();
});

it('does not emit MATCH clause when q is blank', function () {
    DB::enableQueryLog();
    Livewire::test(ProductListing::class);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $hasFT = collect($queries)->contains(fn ($q) => str_contains(strtolower($q['query']), 'match('));
    expect($hasFT)->toBeFalse();
});

it('applies Arabic normalisation to the FULLTEXT binding', function () {
    // "إضاءة" (with alef-hamza + taa-marbuta) must be normalised
    // to "اضاءه" (bare alef + haa) before being bound to MATCH…AGAINST.
    DB::enableQueryLog();
    Livewire::withQueryParams(['q' => 'إضاءة'])->test(ProductListing::class);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $ftEntry = collect($queries)
        ->first(fn ($q) => str_contains(strtolower($q['query']), 'match('));

    expect($ftEntry)->not->toBeNull('Expected a FULLTEXT query to be emitted');

    // binding must be the normalised form, not the raw user input
    $expected = ArabicNorm::sanitizeQuery('إضاءة'); // "اضاءه"
    expect($ftEntry['bindings'])->toContain($expected);
});

it('normalised query for alef variant matches the same canonical form', function () {
    // Both "إضاءة" and "اضاءة" must produce identical canonical bindings,
    // proving that a user's alef variant never diverges from the indexed form.
    $withHamza = ArabicNorm::sanitizeQuery('إضاءة');
    $withoutHamza = ArabicNorm::sanitizeQuery('اضاءة');

    expect($withHamza)->toBe($withoutHamza);
});

// ──────────────────────── Scope filters (no FULLTEXT needed) ────────────────────────

it('scope c:{slug} restricts products to that category', function () {
    $catA = srMakeCat('Lighting', 'lighting-sc');
    $catB = srMakeCat('Electrical', 'electrical-sc');
    $brand = srMakeBrand('ScopeBrand');

    srMakeProduct($catA, $brand, 'lighting product');
    srMakeProduct($catA, $brand, 'another lighting product');
    srMakeProduct($catB, $brand, 'electrical product');

    // No q → scope acts as a category pre-filter only (no FULLTEXT)
    Livewire::withQueryParams(['scope' => 'c:lighting-sc'])
        ->test(ProductListing::class)
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});

it('scope brands filters products by brand name matching q', function () {
    $cat = srMakeCat();
    $philips = srMakeBrand('Philips');
    $osram = srMakeBrand('Osram');

    srMakeProduct($cat, $philips, 'some lamp');
    srMakeProduct($cat, $philips, 'another lamp');
    srMakeProduct($cat, $osram, 'tube light');

    Livewire::withQueryParams(['q' => 'Philips', 'scope' => 'brands'])
        ->test(ProductListing::class)
        ->assertViewHas('products', fn ($p) => $p->total() === 2);
});
