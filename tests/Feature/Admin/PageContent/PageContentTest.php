<?php

use App\Livewire\Admin\PageContent\Edit;
use App\Models\PageSection;
use Database\Seeders\SiteSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(SiteSeeder::class);
    loginAdmin();
});

it('gates the page behind page_content.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.page-content'))->assertForbidden();

    loginAdmin(['page_content.view']);
    $this->get(route('admin.page-content'))->assertOk();
});

it('returns 404 for an unknown page slug', function () {
    $this->get(route('admin.page-content', 'unknown'))->assertNotFound();
});

it('mounts every page without errors', function () {
    foreach (array_keys(config('page_sections', [])) as $page) {
        Livewire::test(Edit::class, ['page' => $page])
            ->assertOk()
            ->assertHasNoErrors();
    }
});

it('pre-fills form from the seeded content', function () {
    $section = PageSection::where(['page' => 'home', 'key' => 'hero'])->firstOrFail();

    $component = Livewire::test(Edit::class, ['page' => 'home']);

    $headline = $section->content['headline'] ?? [];
    $component->assertSet('form.hero.content.headline.en', $headline['en'] ?? '')
        ->assertSet('form.hero.content.headline.ar', $headline['ar'] ?? '');
});

it('saves edited text content to the database', function () {
    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('form.hero.content.headline.en', 'New Headline EN')
        ->set('form.hero.content.headline.ar', 'عنوان جديد')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $row = PageSection::where(['page' => 'home', 'key' => 'hero'])->firstOrFail();
    expect($row->content['headline'])->toEqual(['en' => 'New Headline EN', 'ar' => 'عنوان جديد']);
});

it('saves link fields', function () {
    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('form.hero.content.button1_link', '/new-link')
        ->call('save')
        ->assertHasNoErrors();

    $row = PageSection::where(['page' => 'home', 'key' => 'hero'])->firstOrFail();
    expect($row->content['button1_link'])->toBe('/new-link');
});

it('saves list item text fields', function () {
    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('form.features.content.items.0.text.en', 'Fast delivery')
        ->set('form.features.content.items.0.text.ar', 'توصيل سريع')
        ->call('save')
        ->assertHasNoErrors();

    $row = PageSection::where(['page' => 'home', 'key' => 'features'])->firstOrFail();
    expect($row->content['items'][0]['text'])->toEqual(['en' => 'Fast delivery', 'ar' => 'توصيل سريع']);
});

it('toggles is_active for a section', function () {
    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('form.brands_head.is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(PageSection::where(['page' => 'home', 'key' => 'brands_head'])->value('is_active'))->toBeFalse();
});

it('saves all sections for every page without errors', function () {
    foreach (array_keys(config('page_sections', [])) as $page) {
        Livewire::test(Edit::class, ['page' => $page])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');
    }
});

it('requires page_content.update to save', function () {
    loginAdmin(['page_content.view']);

    Livewire::test(Edit::class, ['page' => 'home'])
        ->call('save')
        ->assertForbidden();
});
