<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Support\ResponsiveImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Conversion;

/**
 * @param  class-string  $model
 * @return array<string, Conversion>
 */
function conversionsFor(string $model): array
{
    $instance = new $model;
    $instance->registerAllMediaConversions();

    $out = [];
    foreach ($instance->mediaConversions as $conversion) {
        /** @var Conversion $conversion */
        $out[$conversion->getName()] = $conversion;
    }

    return $out;
}

it('registers the four responsive conversions on every image model', function (string $model) {
    $conversions = conversionsFor($model);

    expect(array_keys($conversions))->toMatchArray(['thumb', 'card', 'large', 'hero']);
})->with([
    Product::class,
    Category::class,
    Brand::class,
    Service::class,
    Project::class,
    Post::class,
    PageSection::class,
]);

it('encodes every conversion as webp', function () {
    foreach (conversionsFor(Product::class) as $conversion) {
        expect($conversion->getResultExtension('jpg'))->toBe('webp');
    }
});

it('performs product conversions on gallery images but never on the files collection', function () {
    $conversions = conversionsFor(Product::class);

    expect($conversions['card']->shouldBePerformedOn('gallery'))->toBeTrue()
        ->and($conversions['card']->shouldBePerformedOn('files'))->toBeFalse();
});

it('performs brand conversions on image collections but never on the catalogue PDF', function () {
    $conversions = conversionsFor(Brand::class);

    expect($conversions['card']->shouldBePerformedOn('logo'))->toBeTrue()
        ->and($conversions['card']->shouldBePerformedOn('catalogue'))->toBeFalse();
});

it('renders a webp srcset and sizes from a media item via the image-frame component', function () {
    Storage::fake('public');
    // Opt into real (inline) conversion generation for this assertion.
    config(['media-library.queue_connection_name' => 'sync']);

    $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'C'],
        'slug' => ['ar' => 'cat-ar', 'en' => 'cat-en'],
    ]);

    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'sku' => 'LAMP-1',
        'name' => ['en' => 'Lamp', 'ar' => 'مصباح'],
        'slug' => ['en' => 'lamp', 'ar' => 'misbah'],
    ]);

    $file = UploadedFile::fake()->image('lamp.jpg', 1600, 1600);

    $product->addMedia($file->getRealPath())
        ->preservingOriginal()
        ->usingFileName('lamp.jpg')
        ->toMediaCollection('gallery');

    $media = $product->getFirstMedia('gallery');

    expect(ResponsiveImage::srcset($media))->toContain('640w', '.webp');

    $html = $this->blade(
        '<x-image-frame :media="$media" sizes="300px" alt="Lamp" />',
        ['media' => $media],
    );

    $html->assertSee('srcset=', false)
        ->assertSee('640w', false)
        ->assertSee('sizes="300px"', false)
        ->assertSee('loading="lazy"', false);
});

it('renders eager loading and fetchpriority high when props are set', function () {
    Storage::fake('public');
    config(['media-library.queue_connection_name' => 'sync']);

    $brand = Brand::create(['name' => 'Acme2', 'slug' => 'acme2']);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق2', 'en' => 'C2'],
        'slug' => ['ar' => 'cat-ar-2', 'en' => 'cat-en-2'],
    ]);
    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'sku' => 'LAMP-2',
        'name' => ['en' => 'Lamp2', 'ar' => 'مصباح2'],
        'slug' => ['en' => 'lamp-2', 'ar' => 'misbah-2'],
    ]);
    $file = UploadedFile::fake()->image('lamp2.jpg', 1600, 1600);
    $product->addMedia($file->getRealPath())
        ->preservingOriginal()
        ->usingFileName('lamp2.jpg')
        ->toMediaCollection('gallery');
    $media = $product->getFirstMedia('gallery');

    $html = $this->blade(
        '<x-image-frame :media="$media" sizes="300px" alt="Lamp" eager priority />',
        ['media' => $media],
    );

    $html->assertSee('loading="eager"', false)
        ->assertSee('fetchpriority="high"', false);
});

it('lang-switch renders aria-current on active locale and no aria-pressed on anchors', function () {
    $html = $this->blade('<x-site.lang-switch />');

    $html->assertDontSee('aria-pressed', false);

    // One anchor must have aria-current="true" matching the current locale (ar by default in tests)
    $html->assertSee('aria-current="true"', false);
});

it('maps conversion names to ascending widths as a single source of truth', function () {
    expect(ResponsiveImage::SIZES)->toBe([
        'thumb' => 160,
        'card' => 640,
        'large' => 1280,
        'hero' => 1920,
    ]);
});
