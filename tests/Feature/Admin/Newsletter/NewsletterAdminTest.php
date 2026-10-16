<?php

use App\Enums\NewsletterSource;
use App\Livewire\Admin\Newsletter\Index;
use App\Models\NewsletterSubscriber;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function makeSubscriber(array $attributes = []): NewsletterSubscriber
{
    static $n = 0;
    $n++;

    return NewsletterSubscriber::create(array_merge([
        'email' => "sub{$n}@example.com",
        'locale' => 'en',
        'source' => NewsletterSource::Footer,
    ], $attributes));
}

it('renders the newsletter screen', function () {
    makeSubscriber(['email' => 'visible@example.com']);

    $this->get(route('admin.newsletter'))->assertOk()->assertSee('visible@example.com');
});

it('unsubscribes and resubscribes a subscriber', function () {
    $sub = makeSubscriber();

    Livewire::test(Index::class)->call('unsubscribe', $sub->id);
    expect($sub->fresh()->unsubscribed_at)->not->toBeNull();

    Livewire::test(Index::class)->call('resubscribe', $sub->id);
    expect($sub->fresh()->unsubscribed_at)->toBeNull();
});

it('filters by subscribed/unsubscribed tabs', function () {
    makeSubscriber(['email' => 'active@example.com']);
    makeSubscriber(['email' => 'gone@example.com', 'unsubscribed_at' => now()]);

    Livewire::test(Index::class)
        ->set('tab', 'unsubscribed')
        ->assertSee('gone@example.com')
        ->assertDontSee('active@example.com');
});

it('deletes a subscriber', function () {
    $sub = makeSubscriber();

    Livewire::test(Index::class)->call('delete', $sub->id);

    expect(NewsletterSubscriber::find($sub->id))->toBeNull();
});

it('exports a CSV honouring filters', function () {
    makeSubscriber(['email' => 'exported@example.com']);
    makeSubscriber(['email' => 'gone@example.com', 'unsubscribed_at' => now()]);

    $component = Livewire::test(Index::class)->set('tab', 'subscribed');
    $component->call('export')->assertFileDownloaded('newsletter-subscribers-'.now()->format('Y-m-d').'.csv');

    $response = $component->instance()->export();
    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)->toContain('exported@example.com')
        ->and($csv)->not->toContain('gone@example.com');
});

it('gates export behind the export permission', function () {
    loginAdmin(['newsletter.view']);

    Livewire::test(Index::class)->call('export')->assertForbidden();
});
