<?php

use App\Livewire\Admin\Products\Index;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function listBrand(string $name = 'Acme', string $slug = 'acme'): Brand
{
    return Brand::where('slug', $slug)->first() ?? Brand::create(['name' => $name, 'slug' => $slug]);
}

function listCategory(string $en = 'cat'): Category
{
    return Category::where('slug_en', $en)->first()
        ?? Category::create(['level' => 3, 'name' => ['ar' => $en, 'en' => $en], 'slug' => ['ar' => $en, 'en' => $en]]);
}

function listProduct(array $attributes = [], int $stockQty = 20): Product
{
    static $n = 0;
    $n++;

    $product = Product::create(array_merge([
        'brand_id' => listBrand()->id,
        'category_id' => listCategory()->id,
        'name' => ['ar' => "منتج $n", 'en' => "Product $n"],
        'slug' => ['ar' => "منتج-$n", 'en' => "product-$n"],
        'sku' => "SKU-$n",
        'status' => 'active',
        'stock_status' => 'in',
    ], $attributes));

    // stock_status / min_price are cache columns derived from variants, so a
    // product needs at least one variant for those (and the stock filter) to work.
    $product->variants()->create([
        'sku' => $product->sku.'-V',
        'price' => 100,
        'stock_qty' => $stockQty,
        'is_default' => true,
    ]);

    return $product->refresh();
}

it('gates the list behind the products.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.products'))->assertForbidden();

    loginAdmin(['products.view']);
    $this->get(route('admin.products'))->assertOk();
});

it('filters by status tab', function () {
    listProduct(['sku' => 'ACT-1', 'status' => 'active']);
    listProduct(['sku' => 'DRA-1', 'status' => 'draft']);
    listProduct(['sku' => 'ARC-1', 'status' => 'archived']);

    Livewire::test(Index::class)
        ->assertSee('ACT-1')->assertSee('DRA-1')->assertSee('ARC-1')
        ->set('tab', 'draft')
        ->assertSee('DRA-1')->assertDontSee('ACT-1')->assertDontSee('ARC-1');
});

it('searches by sku, name and brand', function () {
    $philips = Brand::create(['name' => 'Philips', 'slug' => 'philips']);
    listProduct(['sku' => 'LAMP-1', 'name' => ['ar' => 'مصباح', 'en' => 'Lamp']]);
    listProduct(['sku' => 'WIRE-1', 'brand_id' => $philips->id]);

    Livewire::test(Index::class)
        ->set('search', 'LAMP')->assertSee('LAMP-1')->assertDontSee('WIRE-1')
        ->set('search', 'Philips')->assertSee('WIRE-1')->assertDontSee('LAMP-1');
});

it('filters by category, brand and stock', function () {
    $other = Category::create(['level' => 3, 'name' => ['ar' => 'آخر', 'en' => 'other'], 'slug' => ['ar' => 'آخر', 'en' => 'other']]);
    $osram = Brand::create(['name' => 'Osram', 'slug' => 'osram']);

    listProduct(['sku' => 'A-1']);                                  // in stock (qty 20)
    listProduct(['sku' => 'B-1', 'category_id' => $other->id], 2);  // low stock (qty 2)
    listProduct(['sku' => 'C-1', 'brand_id' => $osram->id]);

    Livewire::test(Index::class)
        ->set('category', (string) $other->id)->assertSee('B-1')->assertDontSee('A-1')
        ->set('category', '')->set('brand', (string) $osram->id)->assertSee('C-1')->assertDontSee('A-1')
        ->set('brand', '')->set('stock', 'low')->assertSee('B-1')->assertDontSee('A-1');
});

it('bulk publishes and archives selected products', function () {
    $a = listProduct(['status' => 'draft']);
    $b = listProduct(['status' => 'draft']);

    Livewire::test(Index::class)
        ->set('selected', [$a->id, $b->id])
        ->call('bulkPublish')
        ->assertSet('selected', []);

    expect($a->fresh()->status->value)->toBe('active')
        ->and($b->fresh()->status->value)->toBe('active');

    Livewire::test(Index::class)
        ->set('selected', [$a->id])
        ->call('bulkArchive');

    expect($a->fresh()->status->value)->toBe('archived');
});

it('bulk sets and clears a badge', function () {
    $a = listProduct();

    Livewire::test(Index::class)
        ->set('selected', [$a->id])
        ->call('bulkBadge', 'is_best_seller');
    expect($a->fresh()->is_best_seller)->toBeTrue();

    Livewire::test(Index::class)
        ->set('selected', [$a->id])
        ->call('bulkBadge', 'none');
    expect($a->fresh()->is_best_seller)->toBeFalse();
});

it('bulk deletes selected products', function () {
    $a = listProduct();
    $b = listProduct();

    Livewire::test(Index::class)
        ->set('selected', [$a->id, $b->id])
        ->call('bulkDelete');

    expect(Product::whereIn('id', [$a->id, $b->id])->count())->toBe(0);
});

it('duplicates a product as a draft with a new sku and its variants', function () {
    // listProduct already attaches a default variant "<sku>-V".
    $product = listProduct(['sku' => 'ORIG-1', 'status' => 'active']);

    Livewire::test(Index::class)->call('duplicate', $product->id);

    $copy = Product::where('sku', 'ORIG-1-COPY')->firstOrFail();
    expect($copy->status->value)->toBe('draft')
        ->and($copy->variants()->count())->toBe(1)
        ->and($copy->variants()->first()->sku)->toBe('ORIG-1-V-COPY');
});

it('selects all products on the page', function () {
    $a = listProduct();
    $b = listProduct();

    Livewire::test(Index::class)
        ->call('toggleSelectAll', [$a->id, $b->id])
        ->assertSet('selected', [$a->id, $b->id]);
});
