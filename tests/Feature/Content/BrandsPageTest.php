<?php

use App\Enums\ProductStatus;
use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Support\Slug;

beforeEach(function () {
    $this->withoutVite();
});

function makeBrand(array $attrs = []): Brand
{
    $name = $attrs['name'] ?? 'Delta Light';

    return Brand::create(array_merge([
        'name' => $name,
        'slug' => Slug::make($name, 'en'),
        'origin_country' => ['en' => 'Belgium', 'ar' => 'بلجيكا'],
        'description' => ['en' => 'Belgian lighting.', 'ar' => 'إضاءة بلجيكية.'],
        'is_featured' => false,
        'is_authorised' => true,
        'show_on_home' => true,
        'show_in_partners' => true,
        'sort_order' => 0,
        'is_active' => true,
    ], $attrs));
}

// ──────────────────────── Brands list ────────────────────────

it('renders the brands list page in EN', function () {
    makeBrand(['name' => 'Philips']);

    $this->get('/en/brands')->assertOk()->assertSee('Philips');
});

it('renders the brands list page in AR', function () {
    makeBrand(['name' => 'Legrand']);

    $this->get('/ar/brands')->assertOk()->assertSee('Legrand');
});

it('filters brands by search query', function () {
    makeBrand(['name' => 'Flos']);
    makeBrand(['name' => 'Osram', 'slug' => 'osram']);

    $this->get('/en/brands?q=flos')
        ->assertOk()
        ->assertSee('Flos')
        ->assertDontSee('Osram');
});

it('filters brands by letter', function () {
    makeBrand(['name' => 'Artemide', 'slug' => 'artemide']);
    makeBrand(['name' => 'Philips', 'slug' => 'philips']);

    $this->get('/en/brands?letter=A')
        ->assertOk()
        ->assertSee('Artemide')
        ->assertDontSee('Philips');
});

it('shows featured brand banner when no filter active', function () {
    makeBrand(['name' => 'Delta Light', 'is_featured' => true]);

    $this->get('/en/brands')
        ->assertOk()
        ->assertSee('Delta Light');
});

it('hides featured banner when search is active', function () {
    makeBrand(['name' => 'Delta Light', 'is_featured' => true, 'slug' => 'delta-light']);
    makeBrand(['name' => 'ABB', 'slug' => 'abb']);

    // When searching, featured banner is hidden — only grid shows.
    $this->get('/en/brands?q=abb')
        ->assertOk()
        ->assertSee('ABB')
        ->assertDontSee(__('ui.brand_featured'));
});

it('shows no-results message when nothing matches', function () {
    makeBrand(['name' => 'Flos', 'slug' => 'flos']);

    $this->get('/en/brands?q=xyznothing')
        ->assertOk()
        ->assertSee(__('ui.no_brands_filter'));
});

it('does not show inactive brands', function () {
    makeBrand(['name' => 'HiddenBrand', 'slug' => 'hidden-brand', 'is_active' => false]);

    $this->get('/en/brands')->assertOk()->assertDontSee('HiddenBrand');
});

// ──────────────────────── Brand detail ────────────────────────

it('renders the brand detail page in EN', function () {
    $brand = makeBrand(['name' => 'Philips', 'slug' => 'philips']);

    $this->get('/en/brands/philips')
        ->assertOk()
        ->assertSee('Philips')
        ->assertSee('Belgian lighting.');
});

it('renders the brand detail page in AR', function () {
    $brand = makeBrand(['name' => 'Legrand', 'slug' => 'legrand']);

    $this->get('/ar/brands/legrand')
        ->assertOk()
        ->assertSee('Legrand')
        ->assertSee('إضاءة بلجيكية.');
});

it('returns 404 for non-existent brand slug', function () {
    $this->get('/en/brands/no-such-brand')->assertNotFound();
});

it('returns 404 for inactive brand', function () {
    makeBrand(['name' => 'Gone Brand', 'slug' => 'gone-brand', 'is_active' => false]);

    $this->get('/en/brands/gone-brand')->assertNotFound();
});

it('shows products for the brand', function () {
    $brand = makeBrand(['name' => 'Philips', 'slug' => 'philips']);
    $cat = Category::create([
        'name' => ['en' => 'Indoor', 'ar' => 'داخلي'],
        'slug' => ['en' => 'indoor', 'ar' => 'داخلي'],
        'parent_id' => null,
        'sort_order' => 0,
        'is_active' => true,
    ]);
    Product::create([
        'name' => ['en' => 'Philips Bulb', 'ar' => 'لمبة فيليبس'],
        'slug' => ['en' => 'philips-bulb', 'ar' => 'لمبة-فيليبس'],
        'sku' => 'PHB-001',
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'status' => ProductStatus::Active,
    ]);

    $this->get('/en/brands/philips')
        ->assertOk()
        ->assertSee('Philips Bulb');
});

it('derives category tabs from brand products only', function () {
    $brand = makeBrand(['name' => 'Osram', 'slug' => 'osram']);
    $other = makeBrand(['name' => 'ABB', 'slug' => 'abb']);

    $catA = Category::create([
        'name' => ['en' => 'Decorative Lamps', 'ar' => 'لمبات ديكورية'],
        'slug' => ['en' => 'decorative-lamps', 'ar' => 'لمبات-ديكورية'],
        'parent_id' => null, 'sort_order' => 0, 'is_active' => true,
    ]);
    $catB = Category::create([
        'name' => ['en' => 'Industrial Fittings', 'ar' => 'تركيبات صناعية'],
        'slug' => ['en' => 'industrial-fittings', 'ar' => 'تركيبات-صناعية'],
        'parent_id' => null, 'sort_order' => 1, 'is_active' => true,
    ]);

    // Osram has products in catA only; ABB in catB only.
    Product::create([
        'name' => ['en' => 'Osram Deco Lamp X9Z', 'ar' => 'لمبة أوسرام X9Z'],
        'slug' => ['en' => 'osram-deco-lamp-x9z', 'ar' => 'لمبة-اوسرام-x9z'],
        'sku' => 'OS-001', 'brand_id' => $brand->id, 'category_id' => $catA->id,
        'status' => ProductStatus::Active,
    ]);
    Product::create([
        'name' => ['en' => 'ABB Industrial Q7W', 'ar' => 'ABB صناعي Q7W'],
        'slug' => ['en' => 'abb-industrial-q7w', 'ar' => 'abb-صناعي-q7w'],
        'sku' => 'AB-001', 'brand_id' => $other->id, 'category_id' => $catB->id,
        'status' => ProductStatus::Active,
    ]);

    $response = $this->get('/en/brands/osram')->assertOk();

    // Osram's own product and category tab show.
    $response->assertSee('Osram Deco Lamp X9Z');
    // ABB's product must NOT appear on the Osram brand page.
    $response->assertDontSee('ABB Industrial Q7W');
});

it('shows specified-by-dots projects that used the brand', function () {
    $brand = makeBrand(['name' => 'Delta Light', 'slug' => 'delta-light']);
    $cat = Category::create([
        'name' => ['en' => 'Indoor', 'ar' => 'داخلي'], 'slug' => ['en' => 'indoor', 'ar' => 'داخلي'],
        'parent_id' => null, 'sort_order' => 0, 'is_active' => true,
    ]);
    $product = Product::create([
        'name' => ['en' => 'DL Spot', 'ar' => 'سبوت'], 'slug' => ['en' => 'dl-spot', 'ar' => 'سبوت'],
        'sku' => 'DL-001', 'brand_id' => $brand->id, 'category_id' => $cat->id,
        'status' => ProductStatus::Active,
    ]);

    $project = Project::create([
        'title' => ['en' => 'Hotel Lobby', 'ar' => 'لوبي فندق'],
        'slug' => ['en' => 'hotel-lobby', 'ar' => 'لوبي-فندق'],
        'status' => ProjectStatus::Completed,
        'type' => ProjectType::Hospitality,
        'stage' => ProjectStage::Handover,
        'completed_year' => 2025,
        'is_featured' => false,
        'sort_order' => 0,
        'is_active' => true,
    ]);
    $project->products()->attach($product->id);

    $this->get('/en/brands/delta-light')
        ->assertOk()
        ->assertSee('Hotel Lobby')
        ->assertSee(__('ui.specified_by_dots'));
});
