<?php

use App\Models\PageSection;
use Database\Seeders\SiteSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.queue_connection_name' => 'sync']);
    $this->seed(SiteSeeder::class);
});

/** Attach a file to the home hero's `section` collection under a field property. */
function attachHeroMedia(string $field, UploadedFile $file): void
{
    PageSection::where(['page' => 'home', 'key' => 'hero'])->firstOrFail()
        ->addMedia($file->getRealPath())
        ->preservingOriginal()
        ->usingFileName($file->getClientOriginalName())
        ->withCustomProperties(['field' => $field])
        ->toMediaCollection('section');
}

it('renders the hero <video> with a poster attribute from the poster slot', function () {
    attachHeroMedia('video', UploadedFile::fake()->create('hero.mp4', 1000, 'video/mp4'));
    attachHeroMedia('poster', UploadedFile::fake()->image('poster.png', 1920, 1080));

    $html = $this->get('/ar/')->assertOk()->getContent();

    // The hero video element carries a poster so slow connections see an image,
    // not a black box, while it buffers.
    expect($html)->toMatch('/<video\b[^>]*\bposter="[^"]+"/');
});

it('renders exactly one hero <video> (no redundant image-frame video)', function () {
    attachHeroMedia('video', UploadedFile::fake()->create('hero.mp4', 1000, 'video/mp4'));
    attachHeroMedia('poster', UploadedFile::fake()->image('poster.png', 1920, 1080));

    $html = $this->get('/ar/')->assertOk()->getContent();

    expect(substr_count($html, '<video'))->toBe(1);
});

it('falls back to the poster image (no <video>) when the video slot is empty', function () {
    attachHeroMedia('poster', UploadedFile::fake()->image('poster.png', 1920, 1080));

    $html = $this->get('/ar/')->assertOk()->getContent();

    // No video element, and the poster image is present as the eager background.
    expect(substr_count($html, '<video'))->toBe(0);
    expect($html)->toContain('fetchpriority="high"');
});
