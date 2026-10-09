<?php

use App\Livewire\Admin\Brands\Edit;
use App\Livewire\Admin\Brands\Index;
use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

it('gates the list behind the brands.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.brands'))->assertForbidden();

    loginAdmin(['brands.view']);
    $this->get(route('admin.brands'))->assertOk();
});

it('lists and filters brands by origin and status', function () {
    Brand::create(['name' => 'Philips', 'slug' => 'philips', 'origin_country' => ['ar' => 'هولندا', 'en' => 'Netherlands'], 'is_active' => true]);
    Brand::create(['name' => 'Osram', 'slug' => 'osram', 'origin_country' => ['ar' => 'ألمانيا', 'en' => 'Germany'], 'is_active' => false]);

    Livewire::test(Index::class)
        ->assertSee('Philips')
        ->assertSee('Osram')
        ->set('search', 'osram')
        ->assertSee('Osram')
        ->assertDontSee('Philips')
        ->set('search', '')
        ->set('status', 'off')
        ->assertSee('Osram')
        ->assertDontSee('Philips');
});

it('creates a brand and derives the slug from the name', function () {
    Livewire::test(Edit::class)
        ->set('name', 'Light Co')
        ->call('generateSlug')
        ->assertSet('slug', 'light-co')
        ->set('origin_country.en', 'Egypt')
        ->set('origin_country.ar', 'مصر')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.brands'));

    expect(Brand::where('slug', 'light-co')->exists())->toBeTrue();
});

it('requires a unique slug', function () {
    Brand::create(['name' => 'Philips', 'slug' => 'philips']);

    Livewire::test(Edit::class)
        ->set('name', 'Philips 2')
        ->set('slug', 'philips')
        ->call('save')
        ->assertHasErrors('slug');
});

it('enforces a single featured brand when featuring from the list', function () {
    $a = Brand::create(['name' => 'A', 'slug' => 'a', 'is_featured' => true]);
    $b = Brand::create(['name' => 'B', 'slug' => 'b', 'is_featured' => false]);

    Livewire::test(Index::class)->call('feature', $b->id);

    expect($a->fresh()->is_featured)->toBeFalse()
        ->and($b->fresh()->is_featured)->toBeTrue()
        ->and(Brand::where('is_featured', true)->count())->toBe(1);
});

it('enforces a single featured brand when saving the editor', function () {
    $a = Brand::create(['name' => 'A', 'slug' => 'a', 'is_featured' => true]);
    $b = Brand::create(['name' => 'B', 'slug' => 'b', 'is_featured' => false]);

    Livewire::test(Edit::class, ['brand' => $b])
        ->set('is_featured', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->is_featured)->toBeFalse()
        ->and($b->fresh()->is_featured)->toBeTrue()
        ->and(Brand::where('is_featured', true)->count())->toBe(1);
});

it('toggles active and deletes from the list', function () {
    $brand = Brand::create(['name' => 'Gone', 'slug' => 'gone', 'is_active' => true]);

    Livewire::test(Index::class)
        ->call('toggleActive', $brand->id);
    expect($brand->fresh()->is_active)->toBeFalse();

    Livewire::test(Index::class)->call('delete', $brand->id);
    expect(Brand::where('slug', 'gone')->exists())->toBeFalse();
});

it('attaches an uploaded logo to the media collection', function () {
    Storage::fake('public');

    Livewire::test(Edit::class)
        ->set('name', 'Lumen')
        ->set('slug', 'lumen')
        ->set('logoUpload', UploadedFile::fake()->image('logo.png', 200, 200))
        ->call('save')
        ->assertHasNoErrors();

    $brand = Brand::where('slug', 'lumen')->firstOrFail();
    expect($brand->getMedia('logo'))->toHaveCount(1);
});
