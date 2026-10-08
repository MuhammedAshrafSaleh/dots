<?php

use App\Livewire\Admin\Categories\Index;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function makeCategory(array $attributes = []): Category
{
    static $n = 0;
    $n++;

    return Category::create(array_merge([
        'level' => 1,
        'name' => ['ar' => "قسم $n", 'en' => "Category $n"],
        'slug' => ['ar' => "قسم-$n", 'en' => "category-$n"],
        'is_active' => true,
        'sort_order' => $n,
    ], $attributes));
}

function categoryProduct(Category $category, string $sku): Product
{
    $brand = Brand::firstOrCreate(['slug' => 'acme'], ['name' => 'Acme']);

    return Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => $sku, 'en' => $sku],
        'slug' => ['ar' => $sku, 'en' => $sku],
        'sku' => $sku,
    ]);
}

it('gates the tree behind the categories.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.categories'))->assertForbidden();

    loginAdmin(['categories.view']);
    $this->get(route('admin.categories'))->assertOk();
});

it('reorders siblings within a level', function () {
    $a = makeCategory(['sort_order' => 0]);
    $b = makeCategory(['sort_order' => 1]);
    $c = makeCategory(['sort_order' => 2]);

    Livewire::test(Index::class)->call('reorder', [$c->id, $a->id, $b->id]);

    expect($c->fresh()->sort_order)->toBe(0)
        ->and($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(2);
});

it('moves products to the parent when a category is deleted', function () {
    $parent = makeCategory(['level' => 1]);
    $child = makeCategory(['level' => 2, 'parent_id' => $parent->id]);
    $p1 = categoryProduct($child, 'P-1');
    $p2 = categoryProduct($child, 'P-2');

    Livewire::test(Index::class)->call('delete', $child->id);

    expect(Category::find($child->id))->toBeNull()
        ->and($p1->fresh()->category_id)->toBe($parent->id)
        ->and($p2->fresh()->category_id)->toBe($parent->id);
});

it('reparents children to the grandparent when a middle category is deleted', function () {
    $parent = makeCategory(['level' => 1]);
    $group = makeCategory(['level' => 2, 'parent_id' => $parent->id]);
    $leaf = makeCategory(['level' => 3, 'parent_id' => $group->id]);

    Livewire::test(Index::class)->call('delete', $group->id);

    expect(Category::find($group->id))->toBeNull()
        ->and($leaf->fresh()->parent_id)->toBe($parent->id);
});

it('refuses to delete a top-level category that still has products', function () {
    $root = makeCategory(['level' => 1]);
    categoryProduct($root, 'R-1');

    Livewire::test(Index::class)->call('delete', $root->id);

    expect(Category::find($root->id))->not->toBeNull();
});

it('selects a category and saves edited fields and FAQs', function () {
    $category = makeCategory(['level' => 1]);

    $component = Livewire::test(Index::class)
        ->call('select', $category->id)
        ->set('name.en', 'Lighting')
        ->set('name.ar', 'إضاءة')
        ->call('addFaq');

    $key = array_key_first($component->get('faqs'));
    $component->set("faqs.$key.question.en", 'Q?')
        ->set("faqs.$key.question.ar", 'س؟')
        ->set("faqs.$key.answer.en", 'A')
        ->set("faqs.$key.answer.ar", 'ج')
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();
    expect($category->getTranslation('name', 'en'))->toBe('Lighting')
        ->and($category->faqs()->count())->toBe(1);
});

it('adds a main category and a child', function () {
    $parent = makeCategory(['level' => 1]);

    Livewire::test(Index::class)
        ->call('addMain')
        ->call('addChild', $parent->id);

    expect(Category::where('level', 1)->count())->toBeGreaterThanOrEqual(2)
        ->and(Category::where('parent_id', $parent->id)->where('level', 2)->count())->toBe(1);
});
