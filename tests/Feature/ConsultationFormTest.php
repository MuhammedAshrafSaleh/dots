<?php

use App\Enums\ConsultationAreaRange;
use App\Enums\ConsultationProjectType;
use App\Livewire\Site\ConsultationForm;
use App\Models\ConsultationRequest;
use App\Models\PageSection;
use App\Models\Service;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    RateLimiter::clear('consultation.127.0.0.1');
});

function makeService(): Service
{
    return Service::create([
        'number' => '01',
        'title' => ['ar' => 'تصميم الإضاءة', 'en' => 'Lighting design'],
        'slug' => ['ar' => 'تصميم-الاضاءة', 'en' => 'lighting-design'],
        'excerpt' => ['ar' => 'وصف', 'en' => 'Desc'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'included_title' => ['ar' => 'باقة', 'en' => 'Package'],
        'duration_text' => ['ar' => '3 أسابيع', 'en' => '3 weeks'],
        'show_in_footer' => true,
        'sort_order' => 0,
        'is_active' => true,
    ]);
}

it('renders the consultation form', function () {
    $service = makeService();

    Livewire::test(ConsultationForm::class, ['serviceId' => $service->id])
        ->assertSee(__('ui.book_consultation'));
});

it('saves a consultation_request row on valid submission', function () {
    $service = makeService();

    Livewire::test(ConsultationForm::class, ['serviceId' => $service->id])
        ->set('name', 'Ahmed Ali')
        ->set('mobile', '01012345678')
        ->set('projectType', ConsultationProjectType::Apartment->value)
        ->set('areaRange', ConsultationAreaRange::Lt150->value)
        ->set('notes', 'New Cairo villa')
        ->call('submit')
        ->assertSet('submitted', true);

    $row = ConsultationRequest::firstOrFail();
    expect($row->name)->toBe('Ahmed Ali')
        ->and($row->mobile)->toBe('01012345678')
        ->and($row->service_id)->toBe($service->id)
        ->and($row->status->value)->toBe('new');
});

it('strips spaces from mobile before saving', function () {
    makeService();

    Livewire::test(ConsultationForm::class)
        ->set('name', 'Test')
        ->set('mobile', '010 1234 5678')
        ->set('projectType', ConsultationProjectType::Villa->value)
        ->set('areaRange', ConsultationAreaRange::R150To400->value)
        ->call('submit')
        ->assertSet('submitted', true);

    expect(ConsultationRequest::first()->mobile)->toBe('01012345678');
});

it('rejects an invalid mobile number', function () {
    makeService();

    Livewire::test(ConsultationForm::class)
        ->set('name', 'Test')
        ->set('mobile', '09999999999')   // invalid prefix
        ->set('projectType', ConsultationProjectType::Office->value)
        ->set('areaRange', ConsultationAreaRange::R400To1000->value)
        ->call('submit')
        ->assertHasErrors(['mobile']);

    expect(ConsultationRequest::count())->toBe(0);
});

it('silently swallows submissions when the honeypot is filled', function () {
    makeService();

    Livewire::test(ConsultationForm::class)
        ->set('website', 'http://spam.example')
        ->set('name', 'Bot')
        ->set('mobile', '01012345678')
        ->set('projectType', ConsultationProjectType::Apartment->value)
        ->set('areaRange', ConsultationAreaRange::Lt150->value)
        ->call('submit')
        ->assertSet('submitted', true);

    expect(ConsultationRequest::count())->toBe(0);
});

it('rejects a fourth submission from the same IP (rate limit)', function () {
    $service = makeService();

    $valid = fn () => Livewire::test(ConsultationForm::class, ['serviceId' => $service->id])
        ->set('name', 'Ahmed')
        ->set('mobile', '01012345678')
        ->set('projectType', ConsultationProjectType::Apartment->value)
        ->set('areaRange', ConsultationAreaRange::Lt150->value)
        ->call('submit');

    // Three successful submissions
    $valid()->assertSet('submitted', true);
    $valid()->assertSet('submitted', true);
    $valid()->assertSet('submitted', true);

    // Fourth should be rejected with a validation error
    $valid()->assertHasErrors(['name']);

    expect(ConsultationRequest::count())->toBe(3);
});

it('services index page loads and shows service cards', function () {
    makeService();

    PageSection::create(['page' => 'services', 'key' => 'head', 'content' => [
        'eyebrow' => ['ar' => 'الخدمات', 'en' => 'Services'],
        'title' => ['ar' => 'عنوان', 'en' => 'Title'],
        'intro' => ['ar' => 'مقدمة', 'en' => 'Intro'],
        'button_label' => ['ar' => 'تواصل', 'en' => 'Contact'],
    ]]);

    $this->get('/ar/services')->assertOk()->assertSee('تصميم الإضاءة');
});

it('service detail page loads for a valid slug', function () {
    $service = makeService();

    $this->get('/en/services/lighting-design')->assertOk()->assertSee('Lighting design');
    $this->get('/ar/services/'.rawurlencode('تصميم-الاضاءة'))->assertOk()->assertSee('تصميم الإضاءة');
});

it('service detail returns 404 for an unknown slug', function () {
    $this->get('/en/services/no-such-service')->assertNotFound();
});
