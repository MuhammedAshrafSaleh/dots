<?php

use App\Enums\ConsultationAreaRange;
use App\Enums\ConsultationProjectType;
use App\Enums\LeadStatus;
use App\Livewire\Admin\Consultations\Index;
use App\Models\ConsultationRequest;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function makeConsultation(array $attributes = []): ConsultationRequest
{
    return ConsultationRequest::create(array_merge([
        'name' => 'Sara',
        'mobile' => '01500060828',
        'project_type' => ConsultationProjectType::Villa,
        'area_range' => ConsultationAreaRange::R150To400,
        'notes' => 'Needs lighting plan.',
        'locale' => 'en',
        'status' => LeadStatus::New,
    ], $attributes));
}

it('renders the consultations screen', function () {
    makeConsultation(['name' => 'Visible Consult']);

    $this->get(route('admin.consultations'))->assertOk()->assertSee('Visible Consult');
});

it('updates a consultation status and internal notes', function () {
    $req = makeConsultation(['status' => LeadStatus::New]);

    Livewire::test(Index::class)
        ->call('openEdit', $req->id)
        ->set('name', 'Sara Adel')
        ->set('mobile', '01011112222')
        ->set('status', 'converted')
        ->set('admin_notes', 'Booked a site visit.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showDrawer', false);

    $req->refresh();
    expect($req->name)->toBe('Sara Adel')
        ->and($req->status)->toBe(LeadStatus::Converted)
        ->and($req->admin_notes)->toBe('Booked a site visit.');
});

it('filters by project type', function () {
    makeConsultation(['name' => 'Villa One', 'project_type' => ConsultationProjectType::Villa]);
    makeConsultation(['name' => 'Office One', 'project_type' => ConsultationProjectType::Office]);

    Livewire::test(Index::class)
        ->set('type', 'office')
        ->assertSee('Office One')
        ->assertDontSee('Villa One');
});

it('exports a CSV honouring filters', function () {
    makeConsultation(['name' => 'Exported', 'status' => LeadStatus::New]);
    makeConsultation(['name' => 'Skipped', 'status' => LeadStatus::Closed]);

    $component = Livewire::test(Index::class)->set('tab', 'new');
    $component->call('export')->assertFileDownloaded('consultations-'.now()->format('Y-m-d').'.csv');

    $response = $component->instance()->export();
    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)->toContain('Exported')
        ->and($csv)->not->toContain('Skipped');
});

it('forbids updating without the permission', function () {
    loginAdmin(['consultations.view']);
    $req = makeConsultation();

    Livewire::test(Index::class)
        ->call('openEdit', $req->id)
        ->call('save')
        ->assertForbidden();
});
