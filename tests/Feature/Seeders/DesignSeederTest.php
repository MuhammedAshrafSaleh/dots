<?php

use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\Faq;
use App\Models\Governorate;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\PaymentMethod;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Services\Settings;
use Database\Seeders\AttributesSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CommerceSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\GovernoratesSeeder;
use Database\Seeders\SiteSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(GovernoratesSeeder::class);
    $this->seed(AttributesSeeder::class);
    $this->seed(CatalogSeeder::class);
    $this->seed(ContentSeeder::class);
    $this->seed(CommerceSeeder::class);
    $this->seed(SiteSeeder::class);
});

it('seeds the catalog taxonomy, brands and products with media + cache columns', function () {
    expect(Governorate::count())->toBe(27)
        ->and(Category::where('level', 1)->count())->toBe(5)
        ->and(Category::where('level', 3)->count())->toBe(46)
        ->and(Brand::count())->toBe(8)
        ->and(Brand::where('is_featured', true)->count())->toBe(1)
        ->and(Product::count())->toBe(12);

    $p1 = Product::where('sku', 'PH-ARC-450-30')->firstOrFail();
    expect($p1->min_price)->toBe('4850.00')
        ->and($p1->stock_status)->toBe(StockStatus::In)
        ->and($p1->search_text)->toContain('Philips')
        ->and($p1->getMedia('gallery'))->toHaveCount(1)
        ->and($p1->getFirstMedia('gallery')->getCustomProperty('caption'))->toHaveKey('ar');

    $pendants = Category::where('name->en', 'Pendants')->firstOrFail();
    expect($pendants->show_on_home)->toBeTrue()
        ->and($pendants->getMedia('image'))->toHaveCount(1);
});

it('seeds services, projects, blog and content pages with media', function () {
    expect(Service::count())->toBe(4)
        ->and(Service::first()->items()->count())->toBe(4)
        ->and(Faq::whereNull('faqable_type')->count())->toBe(4)
        ->and(Project::where('status', 'current')->count())->toBe(6)
        ->and(Project::where('status', 'completed')->count())->toBe(8)
        ->and(Post::count())->toBe(9)
        ->and(Post::published()->count())->toBe(9)
        ->and(Page::count())->toBe(6);

    $project = Project::where('status', 'current')->where('is_featured', true)->firstOrFail();
    expect($project->getMedia('cover'))->toHaveCount(1)
        ->and($project->getMedia('gallery')->count())->toBeGreaterThan(0);
});

it('seeds all 25 page sections and settings defaults', function () {
    expect(PageSection::count())->toBe(25);

    $hero = PageSection::where('page', 'home')->where('key', 'hero')->firstOrFail();
    expect($hero->field('headline', 'en'))->not->toBe('')
        ->and($hero->getMedia('section')->count())->toBeGreaterThan(0);

    $settings = app(Settings::class);
    expect($settings->get('commerce.order_prefix'))->toBe('DT-')
        ->and($settings->translated('contact.showroom_label', 'ar'))->toBe('المعرض');
});

it('seeds payment and delivery methods, express limited to Cairo & Giza', function () {
    expect(PaymentMethod::count())->toBe(3)
        ->and(DeliveryMethod::count())->toBe(4);

    $express = DeliveryMethod::where('name->en', 'Express (same day)')->firstOrFail();
    expect($express->governorates()->count())->toBe(2);

    $standard = DeliveryMethod::where('name->en', 'Standard delivery')->firstOrFail();
    expect($standard->governorates()->count())->toBe(0); // empty = everywhere
});
