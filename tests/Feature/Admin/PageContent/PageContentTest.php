<?php

use App\Livewire\Admin\PageContent\Edit;
use App\Models\PageSection;
use Database\Seeders\SiteSeeder;
use Illuminate\Http\UploadedFile;
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

// ── MIME-type validation ──────────────────────────────────────────────────────

it('rejects an image file uploaded into a video slot', function () {
    $webp = UploadedFile::fake()->create('still.webp', 100, 'image/webp');

    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__video', $webp)
        ->call('save')
        ->assertHasErrors(['uploads.hero__video']);
});

it('rejects a video file uploaded into an image slot', function () {
    $mp4 = UploadedFile::fake()->create('clip.mp4', 1000, 'video/mp4');

    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__poster', $mp4)
        ->call('save')
        ->assertHasErrors(['uploads.hero__poster']);
});

it('rejects a .mov file in a video slot with a convert-to-MP4 message', function () {
    $mov = UploadedFile::fake()->create('iphone.mov', 2000, 'video/quicktime');

    $errors = Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__video', $mov)
        ->call('save')
        ->assertHasErrors(['uploads.hero__video'])
        ->errors();

    // The .mov-specific guidance, not the generic unsupported-format line.
    expect($errors->first('uploads.hero__video'))->toContain('MOV');
});

it('accepts a valid MP4 in a video slot', function () {
    $mp4 = UploadedFile::fake()->create('hero.mp4', 1000, 'video/mp4');

    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__video', $mp4)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');
});

it('accepts a valid WebM in a video slot', function () {
    $webm = UploadedFile::fake()->create('hero.webm', 1000, 'video/webm');

    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__video', $webm)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');
});

it('accepts a valid image file in an image slot', function () {
    $png = UploadedFile::fake()->image('poster.png', 1920, 1080);

    Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__poster', $png)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');
});

it('carries the Arabic error message on a wrong-type upload', function () {
    $webp = UploadedFile::fake()->create('still.webp', 100, 'image/webp');

    $errors = Livewire::test(Edit::class, ['page' => 'home'])
        ->set('uploads.hero__video', $webp)
        ->call('save')
        ->errors();

    expect($errors->first('uploads.hero__video'))
        ->toContain('MP4');
});
