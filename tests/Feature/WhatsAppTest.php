<?php

use App\Enums\QuoteSource;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withoutVite();
    // Seed the WhatsApp number so the redirect target is known.
    Setting::query()->updateOrCreate(
        ['group' => 'contact', 'key' => 'whatsapp_number'],
        ['value' => '201000000000'],
    );
    // Flush the settings cache so the controller picks up the fresh value.
    cache()->forget('settings:contact.whatsapp_number');
});

// Helper: build a valid signed URL for a given source.
function waUrl(string $source, array $extra = []): string
{
    return URL::signedRoute('wa.redirect', array_merge(
        ['locale' => 'ar', 'source' => $source],
        $extra,
    ));
}

it('logs a quote_request row and redirects for every source', function (string $source) {
    $url = waUrl($source);

    $this->get($url)
        ->assertRedirectContains('wa.me');

    expect(QuoteRequest::where('source', $source)->count())->toBe(1);
})->with(
    array_column(QuoteSource::cases(), 'value'),
);

it('captures locale, ip, user_agent, and session_id on the logged row', function () {
    $url = waUrl('general');

    $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
        ->withHeaders(['User-Agent' => 'TestBrowser/1.0'])
        ->get($url)
        ->assertRedirect();

    $row = QuoteRequest::where('source', 'general')->firstOrFail();

    expect($row->locale)->toBe('ar')
        ->and($row->ip)->toBe('1.2.3.4')
        ->and($row->user_agent)->toBe('TestBrowser/1.0');
});

it('passes model_type and model_id through to the logged row', function () {
    $url = waUrl('product', ['model_type' => 'product', 'model_id' => 42, 'ref_url' => 'http://dots.test/ar/p/foo']);

    $this->get($url)->assertRedirect();

    $row = QuoteRequest::where('source', 'product')->firstOrFail();

    expect($row->sourceable_type)->toBe('product')
        ->and($row->sourceable_id)->toBe(42);
});

it('rejects an unsigned wa redirect with 403', function () {
    $this->get('/ar/wa/general')->assertForbidden();
});

it('rejects an invalid source with 404', function () {
    $url = URL::signedRoute('wa.redirect', ['locale' => 'ar', 'source' => 'not_a_source']);

    $this->get($url)->assertNotFound();
});

it('redirects to the wa.me number from settings', function () {
    $url = waUrl('general');

    $this->get($url)->assertRedirectContains('wa.me/201000000000');
});

it('boq button has no file-input element in the rendered component', function () {
    $view = $this->withoutVite()->blade(
        '<x-whatsapp-button source="boq" />',
    );

    $view->assertDontSee('<input', false);
    $view->assertDontSee('type="file"', false);
});
