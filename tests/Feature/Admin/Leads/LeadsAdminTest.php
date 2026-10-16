<?php

use App\Enums\LeadStatus;
use App\Enums\QuoteSource;
use App\Livewire\Admin\Leads\Index;
use App\Models\QuoteRequest;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function makeLead(array $attributes = []): QuoteRequest
{
    return QuoteRequest::create(array_merge([
        'source' => QuoteSource::Product,
        'contact_name' => 'Mona',
        'contact_mobile' => '01500060828',
        'page_url' => 'https://dots.test/p/lamp',
        'locale' => 'en',
        'status' => LeadStatus::New,
    ], $attributes));
}

it('renders the leads screen', function () {
    makeLead(['contact_name' => 'Visible Lead']);

    $this->get(route('admin.leads'))->assertOk()->assertSee('Visible Lead');
});

it('updates a lead status and contact details', function () {
    $lead = makeLead(['status' => LeadStatus::New]);

    Livewire::test(Index::class)
        ->call('openEdit', $lead->id)
        ->set('contact_name', 'Mona Magdy')
        ->set('contact_mobile', '01011112222')
        ->set('status', 'contacted')
        ->set('admin_notes', 'Called, will visit showroom.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showDrawer', false);

    $lead->refresh();
    expect($lead->contact_name)->toBe('Mona Magdy')
        ->and($lead->status)->toBe(LeadStatus::Contacted)
        ->and($lead->admin_notes)->toBe('Called, will visit showroom.');
});

it('filters by status tab', function () {
    makeLead(['contact_name' => 'New One', 'status' => LeadStatus::New]);
    makeLead(['contact_name' => 'Closed One', 'status' => LeadStatus::Closed]);

    Livewire::test(Index::class)
        ->set('tab', 'closed')
        ->assertSee('Closed One')
        ->assertDontSee('New One');
});

it('exports a CSV honouring filters', function () {
    makeLead(['contact_name' => 'Exported', 'status' => LeadStatus::New]);
    makeLead(['contact_name' => 'Skipped', 'status' => LeadStatus::Closed]);

    $component = Livewire::test(Index::class)->set('tab', 'new');
    $component->call('export')->assertFileDownloaded('whatsapp-leads-'.now()->format('Y-m-d').'.csv');

    $response = $component->instance()->export();
    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)->toContain('Exported')
        ->and($csv)->not->toContain('Skipped');
});

it('forbids updating without the permission', function () {
    loginAdmin(['leads.view']);
    $lead = makeLead();

    Livewire::test(Index::class)
        ->call('openEdit', $lead->id)
        ->call('save')
        ->assertForbidden();
});
