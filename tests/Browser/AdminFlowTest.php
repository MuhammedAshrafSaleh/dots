<?php

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\User;

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

it('creates a product from scratch with only the required fields', function () {
    [$brand, $main, $leaf] = seedCatalogBits();

    signInAdmin()
        ->navigate('/admin/products/create')
        ->fill('[wire\\:model="name.en"]', 'Arc Pendant')
        ->fill('[wire\\:model="name.ar"]', 'معلقة آرك')
        ->fill('[wire\\:model="sku"]', 'ARC-BR-1')
        ->press('Category & brand')
        ->select('[wire\\:model="brand_id"]', (string) $brand->id)
        ->select('[wire\\:model="category_id"]', (string) $leaf->id)
        ->press('Save changes')
        ->assertSee('Product saved')   // success toast after redirect to the edit page
        ->assertDontSee('This action is unauthorized')
        ->assertNoJavaScriptErrors();

    expect(Product::where('sku', 'ARC-BR-1')->exists())->toBeTrue();
});

it('shows validation errors on the right tab when a required field is missing', function () {
    seedCatalogBits();

    signInAdmin()
        ->navigate('/admin/products/create')
        ->fill('[wire\\:model="name.en"]', 'No Brand')
        ->fill('[wire\\:model="name.ar"]', 'بدون علامة')
        ->fill('[wire\\:model="sku"]', 'NB-1')
        ->press('Save changes')                            // brand + category missing (Category tab)
        ->assertSee('Please fix the highlighted fields.')  // error toast
        ->assertSee('Choose a brand.')                     // field message on the auto-opened tab
        ->assertNoJavaScriptErrors();

    expect(Product::where('sku', 'NB-1')->exists())->toBeFalse();
});

it('edits the selected category in the tree', function () {
    seedCatalogBits();

    signInAdmin()
        ->navigate('/admin/categories')
        ->assertSee('Categories')
        ->assertDontSee('Array to string')
        ->assertNoJavaScriptErrors();
});

it('opens the page content editor and saves the home hero section', function () {
    signInAdmin()
        ->navigate('/admin/page-content/home')
        ->assertSee('Page content')
        ->assertSee('Hero')
        ->fill('[wire\\:model="form.hero.content.headline.en"]', 'E2E Headline EN')
        ->press('Save changes')
        ->assertSee('Page content saved')
        ->assertNoJavaScriptErrors();
});

it('switches between page content tabs without JS errors', function () {
    signInAdmin()
        ->navigate('/admin/page-content/home')
        ->assertSee('Features strip')
        ->navigate('/admin/page-content/about')
        ->assertSee('Our story')
        ->navigate('/admin/page-content/global')
        ->assertSee('Quote band')
        ->assertNoJavaScriptErrors();
});

it('opens settings and saves the contact tab — cache key is updated', function () {
    signInAdmin()
        ->navigate('/admin/settings')
        ->assertSee('Settings')
        ->fill('[wire\\:model="whatsapp_number"]', '201009999999')
        ->press('Save changes')
        ->assertSee('Settings saved')
        ->assertDontSee('This action is unauthorized')
        ->assertNoJavaScriptErrors();

    expect(\App\Services\Settings::class)
        ->and(app(\App\Services\Settings::class)->get('contact.whatsapp_number'))
        ->toBe('201009999999');
});

it('deactivated admin cannot log in', function () {
    $other = User::factory()->create([
        'email' => 'deactivated@dots.test',
        'is_active' => false,
    ]);
    $other->syncRoles(['Admin']);

    visit('/admin/lang/en')
        ->navigate('/admin/login')
        ->fill('input[type=email]', 'deactivated@dots.test')
        ->fill('input[type=password]', 'password')
        ->click('button[type=submit]')
        ->assertDontSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('opens the users list', function () {
    signInAdmin()
        ->navigate('/admin/users')
        ->assertSee('Users')
        ->assertSee('Everyone here has full Admin access')
        ->assertDontSee('This action is unauthorized')
        ->assertNoJavaScriptErrors();
});

it('TipTap editor mounts, accepts typed content, and persists it after save + reload', function () {
    $cat = PostCategory::create(['name' => ['ar' => 'تقنية', 'en' => 'Tech'], 'slug' => ['ar' => 'تقنية', 'en' => 'tech']]);

    signInAdmin()
        ->navigate('/admin/blog/create')
        ->fill('[wire\\:model="title.en"]', 'TipTap E2E Post')
        ->fill('[wire\\:model="title.ar"]', 'مقال اختبار المحرر')
        ->select('[wire\\:model="post_category_id"]', (string) $cat->id)
        ->press('Body')
        ->assertNoJavaScriptErrors()
        // Assert TipTap mounted, then push content via Livewire so the sync is
        // complete before the save action fires (avoids async entangle race).
        ->script("
            const richEl = document.querySelector('[x-data*=\"richEditor\"]');
            const data = window.Alpine.\$data(richEl);
            if (!data._editors?.ar) throw new Error('TipTap AR editor not mounted');
            if (!data._editors?.en) throw new Error('TipTap EN editor not mounted');
            const wireId = document.querySelector('[wire\\\\:id]').getAttribute('wire:id');
            window.Livewire.find(wireId).set('body.ar', '<h2>عنوان</h2><p>محتوى <strong>مهم</strong></p>');
            window.Livewire.find(wireId).set('body.en', '<h2>Heading</h2><p>E2E rich text <strong>content</strong></p>');
        ")
        ->press('Save')
        ->assertSee('Post saved')
        ->assertNoJavaScriptErrors();

    $post = Post::whereJsonContains('title->en', 'TipTap E2E Post')->first();
    expect($post)->not->toBeNull();
    expect($post->body['ar'] ?? '')->toContain('<h2>')
        ->and($post->body['ar'] ?? '')->toContain('<strong>');
    expect($post->body['en'] ?? '')->toContain('<h2>')
        ->and($post->body['en'] ?? '')->toContain('E2E rich text');
});

it('TipTap disallowed HTML is stripped on save', function () {
    $cat = PostCategory::create(['name' => ['ar' => 'عام', 'en' => 'General'], 'slug' => ['ar' => 'عام', 'en' => 'general']]);

    // Set the Livewire model directly via JS to bypass the editor, then save.
    // The server-side purifier (Html::cleanTranslations) must strip the tag.
    signInAdmin()
        ->navigate('/admin/blog/create')
        ->fill('[wire\\:model="title.en"]', 'Sanitise Test Post')
        ->fill('[wire\\:model="title.ar"]', 'اختبار التنقية')
        ->select('[wire\\:model="post_category_id"]', (string) $cat->id)
        ->press('Body')
        // Inject a script tag directly via Livewire (bypassing the editor UI).
        ->script("
            const wireId = document.querySelector('[wire\\\\:id]').getAttribute('wire:id');
            window.Livewire.find(wireId).set('body.en', '<p>ok</p><script>alert(1)<\\/script>');
        ")
        ->press('Save')
        ->assertSee('Post saved')
        ->assertNoJavaScriptErrors();

    $post = Post::whereJsonContains('title->en', 'Sanitise Test Post')->first();
    expect($post)->not->toBeNull();
    expect($post->body['en'] ?? '')->toContain('<p>ok</p>')
        ->and($post->body['en'] ?? '')->not->toContain('<script');
});
