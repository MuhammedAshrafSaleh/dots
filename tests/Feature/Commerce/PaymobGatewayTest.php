<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymobGateway;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.paymob.base_url', 'https://pay.test');
    config()->set('services.paymob.secret_key', 'sk_test');
    config()->set('services.paymob.public_key', 'pk_test');
    config()->set('services.paymob.integration_id_card', '55555');
    config()->set('services.paymob.hmac_secret', 'hmac_secret');
});

function pgOrder(float $total = 1000, OrderPaymentStatus $status = OrderPaymentStatus::Pending): Order
{
    return Order::create([
        'contact_name' => 'Mona Adel',
        'contact_mobile' => '01000000000',
        'contact_email' => 'mona@example.com',
        'locale' => 'en',
        'payment_method_code' => 'card',
        'payment_status' => $status,
        'subtotal' => $total,
        'total' => $total,
        'placed_at' => now(),
    ]);
}

/**
 * Build a webhook `obj` and the matching (or deliberately wrong) HMAC the same
 * way Paymob does: concatenate the ordered fields and HMAC-SHA512 them.
 *
 * @return array{obj: array<string, mixed>, hmac: string}
 */
function pgWebhook(Order $order, bool $success = true, string $txnId = '900001', bool $validHmac = true): array
{
    $obj = [
        'id' => $txnId,
        'amount_cents' => (int) round(((float) $order->total) * 100),
        'created_at' => '2026-10-15T10:00:00',
        'currency' => 'EGP',
        'error_occured' => ! $success,
        'has_parent_transaction' => false,
        'integration_id' => 55555,
        'is_3d_secure' => true,
        'is_auth' => false,
        'is_capture' => false,
        'is_refunded' => false,
        'is_standalone_payment' => true,
        'is_voided' => false,
        'owner' => 100,
        'pending' => false,
        'success' => $success,
        'order' => ['id' => 700, 'merchant_order_id' => $order->number],
        'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
        'data' => ['message' => $success ? 'Approved' : 'Declined'],
        'txn_response_code' => $success ? 'APPROVED' : '51',
    ];

    $ordered = [
        $obj['amount_cents'], $obj['created_at'], $obj['currency'],
        $obj['error_occured'] ? 'true' : 'false', 'false', $obj['id'],
        $obj['integration_id'], 'true', 'false', 'false', 'false', 'true',
        'false', $obj['order']['id'], $obj['owner'], 'false',
        $obj['source_data']['pan'], $obj['source_data']['sub_type'],
        $obj['source_data']['type'], $obj['success'] ? 'true' : 'false',
    ];
    $hmac = hash_hmac('sha512', implode('', $ordered), 'hmac_secret');

    return ['obj' => $obj, 'hmac' => $validHmac ? $hmac : str_repeat('0', 128)];
}

it('settles an order on a valid-HMAC successful webhook', function () {
    $order = pgOrder();
    $w = pgWebhook($order, success: true);

    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid);

    $payment = Payment::where('order_id', $order->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->transaction_id)->toBe('900001')
        ->and($payment->card_last4)->toBe('2346')
        ->and($payment->hmac_verified)->toBeTrue();

    expect(WebhookEvent::where('event_id', '900001')->count())->toBe(1);
    expect($order->statusHistories()->count())->toBe(1);
});

it('ignores a webhook whose HMAC does not match', function () {
    $order = pgOrder();
    $w = pgWebhook($order, success: true, validHmac: false);

    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])
        ->assertOk()
        ->assertJson(['ok' => false]);

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Pending)
        ->and(Payment::count())->toBe(0)
        ->and(WebhookEvent::count())->toBe(0);
});

it('processes a duplicate event only once (idempotent)', function () {
    $order = pgOrder();
    $w = pgWebhook($order, success: true);

    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])->assertOk();
    // Second identical delivery: same transaction id.
    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])->assertOk();

    expect(WebhookEvent::where('event_id', '900001')->count())->toBe(1)
        ->and(Payment::where('order_id', $order->id)->count())->toBe(1)
        ->and($order->statusHistories()->count())->toBe(1);
});

it('marks the order failed on a failed-payment webhook', function () {
    $order = pgOrder();
    $w = pgWebhook($order, success: false, txnId: '900002');

    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Failed);

    $payment = Payment::where('order_id', $order->id)->first();
    expect($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->failure_code)->toBe('51')
        ->and($payment->failure_message)->toBe('Declined');
});

it('createCheckout posts an intention and returns the unified checkout URL', function () {
    Http::fake([
        'pay.test/v1/intention/*' => Http::response(['id' => 'int_42', 'client_secret' => 'cs_live_abc'], 200),
    ]);

    $order = pgOrder(2500);
    $url = app(PaymentGateway::class)->createCheckout($order);

    expect($url)->toContain('pay.test/unifiedcheckout/')
        ->and($url)->toContain('publicKey=pk_test')
        ->and($url)->toContain('clientSecret=cs_live_abc');

    $payment = Payment::where('order_id', $order->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->gateway_ref)->toBe('int_42')
        ->and($payment->status)->toBe(PaymentStatus::Pending);

    Http::assertSent(fn ($request) => $request['special_reference'] === $order->number
        && $request['amount'] === 250000
        && $request['payment_methods'] === [55555]);
});

it('throws when the intention API rejects the request', function () {
    Http::fake(['pay.test/v1/intention/*' => Http::response(['detail' => 'bad'], 401)]);

    expect(fn () => app(PaymobGateway::class)->createCheckout(pgOrder()))
        ->toThrow(PaymentException::class);
});

it('reports configured only when every credential is present', function () {
    expect(app(PaymobGateway::class)->isConfigured())->toBeTrue();

    config()->set('services.paymob.integration_id_card', null);
    expect(app(PaymobGateway::class)->isConfigured())->toBeFalse();
});

it('redirects the browser return to confirmation after a verified paid callback', function () {
    $order = pgOrder();

    // Flat callback query mirrors the webhook fields (dotted source_data keys).
    $query = [
        'amount_cents' => (int) round(((float) $order->total) * 100),
        'created_at' => '2026-10-15T10:00:00',
        'currency' => 'EGP',
        'error_occured' => 'false',
        'has_parent_transaction' => 'false',
        'id' => '900100',
        'integration_id' => '55555',
        'is_3d_secure' => 'true',
        'is_auth' => 'false',
        'is_capture' => 'false',
        'is_refunded' => 'false',
        'is_standalone_payment' => 'true',
        'is_voided' => 'false',
        'order' => '700',
        'owner' => '100',
        'pending' => 'false',
        'source_data.pan' => '2346',
        'source_data.sub_type' => 'MasterCard',
        'source_data.type' => 'card',
        'success' => 'true',
        'merchant_order_id' => $order->number,
    ];
    $ordered = [
        $query['amount_cents'], $query['created_at'], $query['currency'],
        'false', 'false', $query['id'], $query['integration_id'], 'true',
        'false', 'false', 'false', 'true', 'false', $query['order'],
        $query['owner'], 'false', $query['source_data.pan'],
        $query['source_data.sub_type'], $query['source_data.type'], 'true',
    ];
    $query['hmac'] = hash_hmac('sha512', implode('', $ordered), 'hmac_secret');

    $this->get('/en/payments/paymob/return?'.http_build_query($query))
        ->assertRedirect(route('order.confirmation', ['en', $order->number]));

    expect($order->fresh()->payment_status)->toBe(OrderPaymentStatus::Paid);
});

it('refreshes the reservation deadline on every createCheckout', function () {
    Http::fake([
        'pay.test/v1/intention/*' => Http::response(['id' => 'int_99', 'client_secret' => 'cs_x'], 200),
    ]);
    config()->set('services.paymob.base_url', 'https://pay.test');

    $order = pgOrder(1000);
    expect($order->reserved_until)->toBeNull();

    app(PaymentGateway::class)->createCheckout($order);

    $order->refresh();
    expect($order->reserved_until)->not->toBeNull()
        ->and($order->reserved_until->isFuture())->toBeTrue()
        // default window is 60 minutes
        ->and($order->reserved_until->greaterThan(now()->addMinutes(55)))->toBeTrue();
});

it('clears the reservation deadline when a card payment settles paid', function () {
    $order = pgOrder();
    $order->update(['reserved_until' => now()->addMinutes(60)]);
    $w = pgWebhook($order, success: true, txnId: '900500');

    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->reserved_until)->toBeNull();
});

it('records a late success after auto-cancellation without un-cancelling or moving stock', function () {
    $order = pgOrder(status: OrderPaymentStatus::Failed);
    // The sweeper already cancelled it (stock restored) before the money landed.
    $order->update(['status' => OrderStatus::Cancelled, 'reserved_until' => null]);

    $w = pgWebhook($order, success: true, txnId: '900600');

    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Cancelled);

    $payment = Payment::where('order_id', $order->id)->first();
    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->hmac_verified)->toBeTrue();

    $last = $order->statusHistories()->get()->last();
    expect($last->getTranslation('note', 'en'))->toContain('AFTER auto-cancellation');
});
