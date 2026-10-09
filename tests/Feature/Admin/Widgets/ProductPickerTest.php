<?php

use App\Livewire\Admin\Widgets\ProductPicker;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

function pickerProduct(string $sku, array $name): Product
{
    $brand = Brand::where('slug', 'acme')->first()
        ?? Brand::create(['name' => 'Acme', 'slug' => 'acme']);
    $category = Category::where('slug_en', 'cat')->first()
        ?? Category::create(['level' => 3, 'name' => ['ar' => 'فئة', 'en' => 'Category'], 'slug' => ['ar' => 'فئة', 'en' => 'cat']]);

    return Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => $name,
        'slug' => ['ar' => $sku, 'en' => $sku],
        'sku' => $sku,
    ]);
}

it('adds, removes and reorders chosen products', function () {
    $a = pickerProduct('AAA-1', ['ar' => 'ألف', 'en' => 'Alpha']);
    $b = pickerProduct('BBB-2', ['ar' => 'باء', 'en' => 'Beta']);
    $c = pickerProduct('CCC-3', ['ar' => 'جيم', 'en' => 'Gamma']);

    Livewire::test(ProductPicker::class)
        ->call('add', $a->id)
        ->call('add', $b->id)
        ->call('add', $c->id)
        ->assertSet('selected', [$a->id, $b->id, $c->id])
        ->call('remove', $b->id)
        ->assertSet('selected', [$a->id, $c->id])
        ->call('reorder', [$c->id, $a->id])
        ->assertSet('selected', [$c->id, $a->id]);
});

it('does not add the same product twice', function () {
    $a = pickerProduct('AAA-1', ['ar' => 'ألف', 'en' => 'Alpha']);

    Livewire::test(ProductPicker::class)
        ->call('add', $a->id)
        ->call('add', $a->id)
        ->assertSet('selected', [$a->id]);
});

it('excludes chosen and explicitly-excluded products from the options', function () {
    $a = pickerProduct('AAA-1', ['ar' => 'ألف', 'en' => 'Alpha']);
    $b = pickerProduct('BBB-2', ['ar' => 'باء', 'en' => 'Beta']);
    $c = pickerProduct('CCC-3', ['ar' => 'جيم', 'en' => 'Gamma']);

    $component = Livewire::test(ProductPicker::class, ['exclude' => $a->id])
        ->call('add', $b->id);

    $values = collect($component->instance()->options())->pluck('value');

    expect($values)->toContain($c->id)
        ->not->toContain($a->id)   // excluded
        ->not->toContain($b->id);  // already chosen
});

it('searches options by sku', function () {
    pickerProduct('LAMP-9', ['ar' => 'مصباح', 'en' => 'Lamp']);
    pickerProduct('WIRE-5', ['ar' => 'سلك', 'en' => 'Wire']);

    $component = Livewire::test(ProductPicker::class)->set('search', 'LAMP');

    $labels = collect($component->instance()->options())->pluck('label');

    expect($labels)->toHaveCount(1)
        ->and($labels->first())->toContain('LAMP-9');
});
