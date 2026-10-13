<?php

use App\Livewire\Admin\Services\Edit;
use App\Livewire\Admin\Services\Index;
use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

it('gates the list behind the services.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.services'))->assertForbidden();

    loginAdmin(['services.view']);
    $this->get(route('admin.services'))->assertOk();
});

it('lists services and searches by title', function () {
    Service::create([
        'number' => '01',
        'title' => ['ar' => 'تصميم الإضاءة', 'en' => 'Lighting Design'],
        'slug' => ['ar' => 'lighting-design-ar', 'en' => 'lighting-design'],
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Service::create([
        'number' => '02',
        'title' => ['ar' => 'التركيب', 'en' => 'Installation'],
        'slug' => ['ar' => 'installation-ar', 'en' => 'installation'],
        'is_active' => true,
        'sort_order' => 2,
    ]);

    // Admin defaults to AR locale; index renders the AR title.
    Livewire::test(Index::class)
        ->assertSee('تصميم الإضاءة')
        ->assertSee('التركيب')
        ->set('search', 'التركيب')
        ->assertSee('التركيب')
        ->assertDontSee('تصميم الإضاءة');
});

it('creates a service with items and FAQs', function () {
    Livewire::test(Edit::class)
        ->set('title.en', 'Lighting Design')
        ->set('title.ar', 'تصميم الإضاءة')
        ->call('generateSlugs')
        ->assertSet('slug.en', 'lighting-design')
        ->set('number.en', '01')
        ->set('excerpt.en', 'We design lighting')
        ->set('excerpt.ar', 'نصمم الإضاءة')
        ->call('addItem')
        ->tap(function ($component) {
            $key = array_key_first($component->get('items'));
            $component->set("items.$key.text.en", 'Site assessment')
                ->set("items.$key.text.ar", 'تقييم الموقع');
        })
        ->call('addFaq')
        ->tap(function ($component) {
            $key = array_key_first($component->get('faqs'));
            $component->set("faqs.$key.question.en", 'How long does it take?')
                ->set("faqs.$key.question.ar", 'كم يستغرق؟')
                ->set("faqs.$key.answer.en", 'Depends on scope.')
                ->set("faqs.$key.answer.ar", 'يعتمد على النطاق.');
        })
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::where('number', '01')->firstOrFail();
    expect($service->getTranslation('title', 'en'))->toBe('Lighting Design')
        ->and($service->items()->count())->toBe(1)
        ->and($service->faqs()->count())->toBe(1);
});

it('requires both title locales', function () {
    Livewire::test(Edit::class)
        ->set('title.en', 'Design')
        ->set('title.ar', '')
        ->call('save')
        ->assertHasErrors('title.ar');
});

it('toggles active from the list', function () {
    $service = Service::create([
        'number' => '01',
        'title' => ['ar' => 'خدمة', 'en' => 'Service'],
        'slug' => ['ar' => 'service-ar', 'en' => 'service'],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class)->call('toggleActive', $service->id);
    expect($service->fresh()->is_active)->toBeFalse();
});

it('deletes a service from the list', function () {
    $service = Service::create([
        'number' => '01',
        'title' => ['ar' => 'خدمة', 'en' => 'Service'],
        'slug' => ['ar' => 'service-ar', 'en' => 'service'],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class)->call('delete', $service->id);
    expect(Service::find($service->id))->toBeNull();
});

it('updates an existing service and syncs items', function () {
    $service = Service::create([
        'number' => '01',
        'title' => ['ar' => 'قديمة', 'en' => 'Old'],
        'slug' => ['ar' => 'old-ar', 'en' => 'old'],
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $item = $service->items()->create(['text' => ['ar' => 'قديم', 'en' => 'Old item'], 'sort_order' => 0]);

    Livewire::test(Edit::class, ['service' => $service])
        ->set('title.en', 'Updated')
        ->set('title.ar', 'محدّثة')
        ->set("items.{$item->id}.text.en", 'Updated item')
        ->call('save')
        ->assertHasNoErrors();

    expect($service->fresh()->getTranslation('title', 'en'))->toBe('Updated')
        ->and($item->fresh()->getTranslation('text', 'en'))->toBe('Updated item');
});

it('attaches cover image to the image media collection', function () {
    Storage::fake('public');

    Livewire::test(Edit::class)
        ->set('title.en', 'Media Test')
        ->set('title.ar', 'اختبار الوسائط')
        ->set('slug.en', 'media-test')
        ->set('slug.ar', 'media-test-ar')
        ->set('imageUpload', UploadedFile::fake()->image('cover.jpg', 800, 600))
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::where('slug->en', 'media-test')->firstOrFail();
    expect($service->getMedia('image'))->toHaveCount(1);
});
