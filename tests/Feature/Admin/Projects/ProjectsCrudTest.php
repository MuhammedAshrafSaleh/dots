<?php

use App\Livewire\Admin\Projects\Edit;
use App\Livewire\Admin\Projects\Index;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function projectTestProduct(): Product
{
    $brand = Brand::where('slug', 'test-brand')->first()
        ?? Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand']);
    $cat = Category::where('slug_en', 'test-cat')->first()
        ?? Category::create(['level' => 3, 'name' => ['ar' => 'فئة', 'en' => 'Test Cat'], 'slug' => ['ar' => 'test-cat-ar', 'en' => 'test-cat']]);

    return Product::where('sku', 'PRJ-TEST-001')->first() ?? Product::create([
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'name' => ['ar' => 'منتج', 'en' => 'Product'],
        'slug' => ['ar' => 'product-ar-prj', 'en' => 'product-prj'],
        'sku' => 'PRJ-TEST-001',
        'status' => 'active',
    ]);
}

it('gates the list behind the projects.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.projects'))->assertForbidden();

    loginAdmin(['projects.view']);
    $this->get(route('admin.projects'))->assertOk();
});

it('lists projects filtered by status tab', function () {
    Project::create([
        'title' => ['ar' => 'مشروع جارٍ', 'en' => 'Current Project'],
        'slug' => ['ar' => 'current-ar', 'en' => 'current-project'],
        'status' => 'current',
        'type' => 'residential',
        'stage' => 'design',
        'progress' => 30,
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Project::create([
        'title' => ['ar' => 'مشروع منجز', 'en' => 'Completed Project'],
        'slug' => ['ar' => 'completed-ar', 'en' => 'completed-project'],
        'status' => 'completed',
        'type' => 'commercial',
        'stage' => 'handover',
        'progress' => 100,
        'completed_year' => 2023,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    Livewire::test(Index::class)
        ->assertSee('مشروع جارٍ')
        ->assertDontSee('مشروع منجز')
        ->set('statusTab', 'completed')
        ->assertSee('مشروع منجز')
        ->assertDontSee('مشروع جارٍ');
});

it('searches projects by title', function () {
    Project::create([
        'title' => ['ar' => 'فيلا النور', 'en' => 'Villa Al-Nour'],
        'slug' => ['ar' => 'villa-ar', 'en' => 'villa-al-nour'],
        'status' => 'current',
        'type' => 'residential',
        'stage' => 'installation',
        'progress' => 60,
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Project::create([
        'title' => ['ar' => 'مكتب الشركة', 'en' => 'Office Block'],
        'slug' => ['ar' => 'office-ar', 'en' => 'office-block'],
        'status' => 'current',
        'type' => 'commercial',
        'stage' => 'commissioning',
        'progress' => 80,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    Livewire::test(Index::class)
        ->set('search', 'فيلا')
        ->assertSee('فيلا النور')
        ->assertDontSee('مكتب الشركة');
});

it('creates a project with required fields', function () {
    Livewire::test(Edit::class)
        ->set('title.en', 'Test Project')
        ->set('title.ar', 'مشروع تجريبي')
        ->call('generateSlugs')
        ->assertSet('slug.en', 'test-project')
        ->set('status', 'current')
        ->set('type', 'residential')
        ->set('stage', 'design')
        ->set('progress', 25)
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::where('slug->en', 'test-project')->firstOrFail();
    expect($project->getTranslation('title', 'en'))->toBe('Test Project')
        ->and($project->getTranslation('title', 'ar'))->toBe('مشروع تجريبي')
        ->and($project->status->value)->toBe('current')
        ->and($project->type->value)->toBe('residential');
});

it('requires both title locales', function () {
    Livewire::test(Edit::class)
        ->set('title.en', 'Only EN')
        ->set('title.ar', '')
        ->set('slug.en', 'only-en')
        ->set('slug.ar', 'only-en-ar')
        ->call('save')
        ->assertHasErrors('title.ar');
});

it('toggles active from the list', function () {
    $project = Project::create([
        'title' => ['ar' => 'مشروع', 'en' => 'Project'],
        'slug' => ['ar' => 'project-ar', 'en' => 'project'],
        'status' => 'current',
        'type' => 'residential',
        'stage' => 'design',
        'progress' => 0,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class)->call('toggleActive', $project->id);
    expect($project->fresh()->is_active)->toBeFalse();
});

it('deletes a project from the list', function () {
    $project = Project::create([
        'title' => ['ar' => 'مشروع محذوف', 'en' => 'Delete Me'],
        'slug' => ['ar' => 'delete-me-ar', 'en' => 'delete-me'],
        'status' => 'current',
        'type' => 'commercial',
        'stage' => 'design',
        'progress' => 0,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class)->call('delete', $project->id);
    expect(Project::find($project->id))->toBeNull();
});

it('updates an existing project', function () {
    $project = Project::create([
        'title' => ['ar' => 'قديم', 'en' => 'Old Title'],
        'slug' => ['ar' => 'old-ar', 'en' => 'old-title'],
        'status' => 'current',
        'type' => 'residential',
        'stage' => 'design',
        'progress' => 10,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Edit::class, ['project' => $project])
        ->set('title.en', 'Updated Title')
        ->set('title.ar', 'عنوان محدّث')
        ->set('progress', 50)
        ->call('save')
        ->assertHasNoErrors();

    expect($project->fresh()->getTranslation('title', 'en'))->toBe('Updated Title')
        ->and($project->fresh()->progress)->toBe(50);
});

it('syncs products and services to a project', function () {
    $project = Project::create([
        'title' => ['ar' => 'مشروع ربط', 'en' => 'Link Project'],
        'slug' => ['ar' => 'link-ar', 'en' => 'link-project'],
        'status' => 'current',
        'type' => 'residential',
        'stage' => 'design',
        'progress' => 0,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = projectTestProduct();

    $service = Service::create([
        'number' => '01',
        'title' => ['ar' => 'خدمة', 'en' => 'Service'],
        'slug' => ['ar' => 'service-ar', 'en' => 'service'],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Edit::class, ['project' => $project])
        ->call('addProduct', $product->id)
        ->call('addService', $service->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($project->fresh()->products()->count())->toBe(1)
        ->and($project->fresh()->services()->count())->toBe(1);
});

it('attaches cover image to the cover media collection', function () {
    Storage::fake('public');

    Livewire::test(Edit::class)
        ->set('title.en', 'Cover Test')
        ->set('title.ar', 'اختبار الغلاف')
        ->set('slug.en', 'cover-test')
        ->set('slug.ar', 'cover-test-ar')
        ->set('imageUpload', UploadedFile::fake()->image('cover.jpg', 800, 600))
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::where('slug->en', 'cover-test')->firstOrFail();
    expect($project->getMedia('cover'))->toHaveCount(1);
});
