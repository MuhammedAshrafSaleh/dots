<?php

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\AttributesSeeder;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * Renders every admin catalog page against the seeded design catalog, as a
 * real authenticated admin GET (full route middleware + Livewire mount). This
 * catches mount/render crashes (e.g. translatable fields treated as strings)
 * that component-level Livewire::test() does not exercise.
 */
beforeEach(function () {
    Storage::fake('public');
    $this->seed(AttributesSeeder::class);
    $this->seed(CatalogSeeder::class);
    loginAdmin();
});

it('renders every catalog list', function () {
    foreach (['admin.attributes', 'admin.brands', 'admin.categories', 'admin.products'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee('Array to string');
    }
});

it('renders the blog list and create page', function () {
    foreach (['admin.blog', 'admin.blog.create', 'admin.blog.taxonomy'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee('Array to string');
    }
});

it('renders every create page', function () {
    foreach (['admin.attributes.create', 'admin.brands.create', 'admin.products.create'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee('Array to string');
    }
});

it('opens every seeded product edit page without error', function () {
    expect(Product::count())->toBeGreaterThan(0);

    Product::all()->each(function (Product $product) {
        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertDontSee('Array to string')
            ->assertDontSee('This action is unauthorized');
    });
});

it('opens every seeded brand edit page without error', function () {
    Brand::all()->each(function (Brand $brand) {
        $this->get(route('admin.brands.edit', $brand))->assertOk()->assertDontSee('Array to string');
    });
});

it('opens every seeded attribute edit page without error', function () {
    expect(Attribute::count())->toBeGreaterThan(0);

    Attribute::all()->each(function (Attribute $attribute) {
        $this->get(route('admin.attributes.edit', $attribute))->assertOk()->assertDontSee('Array to string');
    });
});

it('renders the categories tree with the first category selected', function () {
    expect(Category::count())->toBeGreaterThan(0);

    $this->get(route('admin.categories'))->assertOk()->assertDontSee('Array to string');
});
