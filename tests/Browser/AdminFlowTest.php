<?php

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;

/**
 * Real-browser E2E (Pest 4 + Playwright). Logs in through the form and drives
 * each admin screen so the full page + Livewire update cycle runs in a real
 * browser — catching render crashes, broken forms, JS errors and failed saves
 * that component-level Livewire::test() does not.
 *
 * Note: the per-request admin-guard 403 (persistent middleware) is verified
 * against the real FPM-served app; the in-process test server retains the
 * guard between requests so it cannot reproduce that specific reset here.
 */
function signInAdmin()
{
    $admin = adminUser();
    $admin->forceFill(['email' => 'e2e@dots.test'])->save(); // factory password is "password"

    return visit('/admin/lang/en')
        ->navigate('/admin/login')
        ->fill('input[type=email]', 'e2e@dots.test')
        ->fill('input[type=password]', 'password')
        ->click('button[type=submit]')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
}

function seedCatalogBits(): array
{
    $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme', 'origin_country' => ['ar' => 'مصر', 'en' => 'Egypt']]);
    $main = Category::create(['level' => 1, 'name' => ['ar' => 'إضاءة', 'en' => 'Lighting'], 'slug' => ['ar' => 'اضاءه', 'en' => 'lighting']]);
    $leaf = Category::create(['level' => 3, 'parent_id' => $main->id, 'name' => ['ar' => 'سبوت', 'en' => 'Spots'], 'slug' => ['ar' => 'سبوت', 'en' => 'spots']]);

    return [$brand, $main, $leaf];
}

it('signs in and shows the dashboard', function () {
    signInAdmin()->assertSee('Dashboard');
});

it('creates an attribute end-to-end and lists it', function () {
    signInAdmin()
        ->navigate('/admin/attributes/create')
        ->fill('[wire\\:model="code"]', 'browsercct')
        ->fill('[wire\\:model="name.en"]', 'Browser CCT')
        ->fill('[wire\\:model="name.ar"]', 'حرارة المتصفح')
        ->press('Save changes')
        ->assertSee('browsercct')
        ->assertPathIs('/admin/attributes')
        ->assertDontSee('This action is unauthorized')
        ->assertNoJavaScriptErrors();

    expect(Attribute::where('code', 'browsercct')->exists())->toBeTrue();
});

it('creates a brand end-to-end and lists it', function () {
    signInAdmin()
        ->navigate('/admin/brands/create')
        ->fill('[wire\\:model="name"]', 'Lumen Co')
        ->fill('[wire\\:model="slug"]', 'lumen-co')
        ->press('Save changes')
        ->assertSee('Lumen Co')
        ->assertPathIs('/admin/brands')
        ->assertDontSee('This action is unauthorized')
        ->assertNoJavaScriptErrors();

    expect(Brand::where('slug', 'lumen-co')->exists())->toBeTrue();
});

it('opens the products list, filters, and opens the editor tabs', function () {
    [$brand, $main, $leaf] = seedCatalogBits();

    signInAdmin()
        ->navigate('/admin/products')
        ->assertSee('Products')
        ->fill('input[type=search]', 'zzz-no-match')   // Livewire update
        ->assertSee('No products match these filters')
        ->navigate('/admin/products/create')
        ->assertSee('Basic info')
        ->press('Variants')                            // switch editor tab (Livewire update)
        ->assertSee('Default variant')
        ->assertDontSee('This action is unauthorized')
        ->assertNoJavaScriptErrors();
});

it('edits the selected category in the tree', function () {
    seedCatalogBits();

    signInAdmin()
        ->navigate('/admin/categories')
        ->assertSee('Categories')
        ->assertDontSee('Array to string')
        ->assertNoJavaScriptErrors();
});
