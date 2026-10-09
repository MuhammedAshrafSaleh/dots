<?php

use App\Livewire\Admin\Products\Edit;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function editBrand(): Brand
{
    return Brand::where('slug', 'acme')->first() ?? Brand::create(['name' => 'Acme', 'slug' => 'acme']);
}

function editLeaf(): Category
{
    return Category::where('slug_en', 'leaf')->first()
        ?? Category::create(['level' => 3, 'name' => ['ar' => 'ورقي', 'en' => 'Leaf'], 'slug' => ['ar' => 'ورقي', 'en' => 'leaf']]);
}

function axisAttribute(string $code, array $values): Attribute
{
    $attr = Attribute::create(['code' => $code, 'name' => ['ar' => $code, 'en' => $code], 'display' => 'chip', 'is_variant_axis' => true]);
    foreach ($values as $i => $v) {
        $attr->values()->create(['value' => $v, 'label' => ['ar' => $v, 'en' => $v], 'sort_order' => $i]);
    }

    return $attr->load('values');
}

it('gates create behind the products.create permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.products.create'))->assertForbidden();

    loginAdmin(['products.create', 'attributes.view']);
    $this->get(route('admin.products.create'))->assertOk();
});

it('starts with a single default variant when there are no axes', function () {
    Livewire::test(Edit::class)
        ->assertSet('defaultKey', 'default')
        ->assertCount('variantRows', 1);
});

it('generates the cartesian product of the selected axis values', function () {
    $cct = axisAttribute('cct', ['2700', '3000']);
    $finish = axisAttribute('finish', ['black', 'white', 'brass']);

    $component = Livewire::test(Edit::class)
        ->set('newAxis', (string) $cct->id)->call('addAxis')
        ->set('newAxis', (string) $finish->id)->call('addAxis');

    // Each axis starts with its first value selected → 1 × 1 = 1 combo.
    expect($component->get('variantRows'))->toHaveCount(1);

    // Add the remaining values: 2 CCT × 3 finishes = 6 variants.
    $component->call('toggleAxisValue', 0, $cct->values[1]->id)
        ->call('toggleAxisValue', 1, $finish->values[1]->id)
        ->call('toggleAxisValue', 1, $finish->values[2]->id);

    expect($component->get('variantRows'))->toHaveCount(6);
});

it('rejects a variant SKU already used by another product', function () {
    $existing = Product::create([
        'brand_id' => editBrand()->id, 'category_id' => editLeaf()->id,
        'name' => ['ar' => 'x', 'en' => 'x'], 'slug' => ['ar' => 'x', 'en' => 'x'], 'sku' => 'EXIST',
    ]);
    $existing->variants()->create(['sku' => 'TAKEN', 'price' => 10, 'stock_qty' => 1, 'is_default' => true]);

    Livewire::test(Edit::class)
        ->set('sku', 'NEW-1')
        ->set('name.en', 'New')->set('name.ar', 'جديد')
        ->set('slug.en', 'new-1')->set('slug.ar', 'جديد-1')
        ->set('brand_id', editBrand()->id)
        ->set('category_id', editLeaf()->id)
        ->set('variantRows.default.sku', 'TAKEN')
        ->call('save')
        ->assertHasErrors('variantRows.default.sku');

    expect(Product::where('sku', 'NEW-1')->exists())->toBeFalse();
});

it('creates a product and updates its cache columns from the default variant', function () {
    Livewire::test(Edit::class)
        ->set('sku', 'ARC-1')
        ->set('name.en', 'Arc Pendant')->set('name.ar', 'معلقة آرك')
        ->set('slug.en', 'arc-pendant')->set('slug.ar', 'معلقة-آرك')
        ->set('brand_id', editBrand()->id)
        ->set('category_id', editLeaf()->id)
        ->set('status', 'active')
        ->set('variantRows.default.sku', 'ARC-1')
        ->set('variantRows.default.price', '500')
        ->set('variantRows.default.stock_qty', 20)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.products'));

    $product = Product::where('sku', 'ARC-1')->firstOrFail();
    expect($product->variants()->count())->toBe(1)
        ->and((float) $product->min_price)->toBe(500.0)
        ->and($product->stock_status->value)->toBe('in')
        ->and($product->status->value)->toBe('active')
        ->and($product->published_at)->not->toBeNull();
});

it('persists generated variants with their attribute values on save', function () {
    $cct = axisAttribute('cct', ['2700', '3000']);

    $component = Livewire::test(Edit::class)
        ->set('sku', 'VX')
        ->set('name.en', 'V')->set('name.ar', 'في')
        ->set('slug.en', 'vx')->set('slug.ar', 'في-اكس')
        ->set('brand_id', editBrand()->id)
        ->set('category_id', editLeaf()->id)
        ->set('newAxis', (string) $cct->id)->call('addAxis')
        ->call('toggleAxisValue', 0, $cct->values[1]->id);

    // Two variants now; give them unique SKUs.
    $keys = array_keys($component->get('variantRows'));
    $component->set("variantRows.{$keys[0]}.sku", 'VX-2700')
        ->set("variantRows.{$keys[1]}.sku", 'VX-3000')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('sku', 'VX')->firstOrFail();
    expect($product->variants()->count())->toBe(2)
        ->and($product->variants()->first()->attributeValues()->count())->toBe(1);
});

it('edits an existing product and syncs specs', function () {
    $product = Product::create([
        'brand_id' => editBrand()->id, 'category_id' => editLeaf()->id,
        'name' => ['ar' => 'قديم', 'en' => 'Old'], 'slug' => ['ar' => 'قديم', 'en' => 'old'], 'sku' => 'OLD-1',
    ]);
    $product->variants()->create(['sku' => 'OLD-1-V', 'price' => 100, 'stock_qty' => 5, 'is_default' => true]);

    $component = Livewire::test(Edit::class, ['product' => $product])
        ->assertSet('sku', 'OLD-1')
        ->set('name.en', 'Renamed')
        ->call('addSpec');

    $key = array_key_first($component->get('specs'));
    $component->set("specs.$key.name.en", 'Power')->set("specs.$key.name.ar", 'القدرة')
        ->set("specs.$key.value.en", '24W')->set("specs.$key.value.ar", '24 وات')
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh();
    expect($product->getTranslation('name', 'en'))->toBe('Renamed')
        ->and($product->specs()->count())->toBe(1);
});

it('deletes a product from the editor', function () {
    $product = Product::create([
        'brand_id' => editBrand()->id, 'category_id' => editLeaf()->id,
        'name' => ['ar' => 'ح', 'en' => 'Del'], 'slug' => ['ar' => 'ح', 'en' => 'del'], 'sku' => 'DEL-1',
    ]);

    Livewire::test(Edit::class, ['product' => $product])
        ->call('deleteProduct')
        ->assertRedirect(route('admin.products'));

    expect(Product::find($product->id))->toBeNull();
});
