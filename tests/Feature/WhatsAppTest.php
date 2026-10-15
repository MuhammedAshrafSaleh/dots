<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\QuoteSource;
use App\Models\Order;
use App\Models\PageSection;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\WhatsApp;
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

it('substitutes order_number and total into the instapay_order message', function () {
    Setting::query()->updateOrCreate(
        ['group' => 'whatsapp', 'key' => 'tpl_instapay_order'],
        ['value' => ['ar' => 'طلب {order_number} — {total}', 'en' => 'Order {order_number} — {total}']],
    );
    cache()->forget('settings:whatsapp.tpl_instapay_order');

    $order = Order::create([
        'contact_name' => 'Mona',
        'contact_mobile' => '01000000000',
        'is_company' => false,
        'locale' => 'ar',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::AwaitingTransfer,
        'payment_method_code' => 'instapay_whatsapp',
        'subtotal' => 2500,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 2500,
        'vat_rate' => 14,
        'vat_amount' => 0,
        'placed_at' => now(),
    ]);

    $url = waUrl('instapay_order', ['model_type' => 'order', 'model_id' => $order->id]);

    $response = $this->get($url)->assertRedirect();
    $target = rawurldecode($response->headers->get('Location'));

    expect($target)
        ->toContain($order->number)
        ->toContain('2,500')
        ->not->toContain('{order_number}')
        ->not->toContain('{total}');
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

// ── Number normalisation ──────────────────────────────────────────────────────

it('normalizes a local Egyptian number to international format', function (string $raw, string $expected) {
    expect(WhatsApp::normalizeNumber($raw))->toBe($expected);
})->with([
    'local with leading zero' => ['01500060828', '201500060828'],
    'already international' => ['201000000000', '201000000000'],
    'with spaces and dashes' => ['0150 006-0828', '201500060828'],
    'with plus prefix' => ['+201000000000', '201000000000'],
    'empty string' => ['', ''],
]);

it('redirects to the normalized number when the setting stores a local number', function () {
    Setting::query()->updateOrCreate(
        ['group' => 'contact', 'key' => 'whatsapp_number'],
        ['value' => '01500060828'],
    );
    cache()->forget('settings:contact.whatsapp_number');

    $url = waUrl('general');

    $this->get($url)->assertRedirectContains('wa.me/201500060828');
});

// ── Rendered public buttons must use the signed logging route ─────────────────

it('quote band button href points to the signed wa.redirect route, not wa.me', function () {
    // Create the minimum page section so the component renders.
    PageSection::query()->updateOrCreate(
        ['page' => 'global', 'key' => 'quote_band'],
        ['content' => [
            'eyebrow' => ['ar' => 'اقتباس', 'en' => 'Quote'],
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'button_label' => ['ar' => 'واتساب', 'en' => 'WhatsApp'],
            'hours_text' => ['ar' => 'الأحد-الجمعة', 'en' => 'Sun-Fri'],
            'bullets' => [],
        ]],
    );
    app()->setLocale('ar');

    $view = $this->withoutVite()->blade('<x-site.quote-band locale="ar" />');

    $html = (string) $view;

    expect($html)
        ->toContain('/ar/wa/boq')
        ->not->toContain('wa.me');
});

it('nav header quote link href points to the signed wa.redirect route, not wa.me', function () {
    // Set URL defaults so locale-prefixed route helpers resolve without throwing.
    URL::defaults(['locale' => 'ar']);

    $view = $this->withoutVite()->blade('<x-site.header locale="ar" />');

    $html = (string) $view;

    expect($html)
        ->toContain('/ar/wa/general')
        ->not->toContain('wa.me');
});
