<?php

use App\Actions\ReleaseExpiredReservations;
use App\Enums\CouponType;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Coupons;
use Illuminate\Support\Carbon;

/** Build a variant with its product scaffolding, returning the variant. */
function relVariant(string $sku, int $stock = 10): ProductVariant
{
    $brand = Brand::create(['name' => 'B'.$sku, 'slug' => 'b-'.strtolower($sku)]);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'C'],
        'slug' => ['ar' => 'ق-'.$sku, 'en' => 'c-'.strtolower($sku)],
    ]);
    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'منتج '.$sku, 'en' => 'Product '.$sku],
        'slug' => ['ar' => 'م-'.$sku, 'en' => 'p-'.strtolower($sku)],
        'sku' => $sku,
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'price_on_request' => false,
        'min_price' => 1000,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $sku.'-V',
        'price' => 1000,
        'stock_qty' => $stock,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

/** An InstaPay order awaiting transfer, holding `qty` units of `$variant`. */
function relAwaitingOrder(ProductVariant $variant, int $qty, ?Carbon $reservedUntil): Order
{
    $order = Order::create([
        'contact_name' => 'Mona',
        'contact_mobile' => '01000000000',
        'is_company' => false,
        'locale' => 'ar',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::AwaitingTransfer,
        'payment_method_code' => PaymentMethodCode::InstapayWhatsapp->value,
        'subtotal' => 1000 * $qty,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 1000 * $qty,
        'vat_rate' => 14,
        'vat_amount' => 0,
        'reserved_until' => $reservedUntil,
        'placed_at' => now(),
    ]);

    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'منتج', 'en' => 'Product'],
        'variant_label' => ['ar' => '', 'en' => ''],
        'sku' => $variant->sku,
        'unit_price' => 1000,
        'qty' => $qty,
        'line_total' => 1000 * $qty,
    ]);

    $order->statusHistories()->create([
        'status' => OrderStatus::New->value,
        'created_at' => now(),
    ]);

    return $order;
}

/** A card order holding `qty` units of `$variant`, in the given payment state. */
function relCardOrder(ProductVariant $variant, int $qty, ?Carbon $reservedUntil, OrderPaymentStatus $status = OrderPaymentStatus::Failed): Order
{
    $order = Order::create([
        'contact_name' => 'Karim',
        'contact_mobile' => '01000000000',
        'is_company' => false,
        'locale' => 'ar',
        'status' => OrderStatus::New,
        'payment_status' => $status,
        'payment_method_code' => PaymentMethodCode::Card->value,
        'subtotal' => 1000 * $qty,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 1000 * $qty,
        'vat_rate' => 14,
        'vat_amount' => 0,
        'reserved_until' => $reservedUntil,
        'placed_at' => now(),
    ]);

    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'منتج', 'en' => 'Product'],
        'variant_label' => ['ar' => '', 'en' => ''],
        'sku' => $variant->sku,
        'unit_price' => 1000,
        'qty' => $qty,
        'line_total' => 1000 * $qty,
    ]);

    return $order;
}

/**
 * Build a declined Paymob webhook `obj` + matching HMAC for `$order`, the same
 * ordered-field concatenation Paymob signs with.
 *
 * @return array{obj: array<string, mixed>, hmac: string}
 */
function relFailedWebhook(Order $order): array
{
    $obj = [
        'id' => '905001',
        'amount_cents' => (int) round(((float) $order->total) * 100),
        'created_at' => '2026-10-15T10:00:00',
        'currency' => 'EGP',
        'error_occured' => true,
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
        'success' => false,
        'order' => ['id' => 700, 'merchant_order_id' => $order->number],
        'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
        'data' => ['message' => 'Declined'],
        'txn_response_code' => '51',
    ];

    $ordered = [
        $obj['amount_cents'], $obj['created_at'], $obj['currency'],
        'true', 'false', $obj['id'], $obj['integration_id'], 'true',
        'false', 'false', 'false', 'true', 'false', $obj['order']['id'],
        $obj['owner'], 'false', $obj['source_data']['pan'],
        $obj['source_data']['sub_type'], $obj['source_data']['type'], 'false',
    ];

    return ['obj' => $obj, 'hmac' => hash_hmac('sha512', implode('', $ordered), 'hmac_secret')];
}

it('cancels an expired reservation, restores stock and notes it on the timeline', function () {
    $variant = relVariant('A', stock: 7); // 3 already reserved out of 10
    $order = relAwaitingOrder($variant, qty: 3, reservedUntil: now()->subMinute());

    $released = app(ReleaseExpiredReservations::class)->handle();

    expect($released)->toBe(1);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->reserved_until)->toBeNull()
        ->and($variant->fresh()->stock_qty)->toBe(10); // 7 + 3 restored

    $last = $order->statusHistories()->get()->last();
    expect($last->status)->toBe(OrderStatus::Cancelled->value)
        ->and($last->getTranslation('note', 'en'))->toContain('expired');
});

it('leaves a reservation that has not yet expired untouched', function () {
    $variant = relVariant('B', stock: 7);
    $order = relAwaitingOrder($variant, qty: 3, reservedUntil: now()->addHour());

    $released = app(ReleaseExpiredReservations::class)->handle();

    expect($released)->toBe(0);
    expect($order->fresh()->status)->toBe(OrderStatus::New)
        ->and($variant->fresh()->stock_qty)->toBe(7);
});

it('releases only once reserved_until passes (time travel)', function () {
    $variant = relVariant('C', stock: 8);
    $order = relAwaitingOrder($variant, qty: 2, reservedUntil: now()->addHours(24));

    // Before the window closes: nothing happens.
    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(0);
    expect($order->fresh()->status)->toBe(OrderStatus::New);

    // Jump past the hold window and run again: the order is released once.
    $this->travel(25)->hours();

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(1);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock_qty)->toBe(10);

    // Idempotent: a second run finds nothing and never double-restores.
    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(0);
    expect($variant->fresh()->stock_qty)->toBe(10);

    $this->travelBack();
});

it('runs via the scheduled console command', function () {
    $variant = relVariant('D', stock: 5);
    relAwaitingOrder($variant, qty: 2, reservedUntil: now()->subMinute());

    $this->artisan('orders:release-reservations')
        ->expectsOutputToContain('Released 1 expired reservation(s).')
        ->assertSuccessful();

    expect($variant->fresh()->stock_qty)->toBe(7);
});

it('releases a failed card order past its window, once, with a card-reason note', function () {
    $variant = relVariant('E', stock: 7); // 3 reserved out of 10
    $order = relCardOrder($variant, qty: 3, reservedUntil: now()->subMinute(), status: OrderPaymentStatus::Failed);

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(1);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->reserved_until)->toBeNull()
        ->and($variant->fresh()->stock_qty)->toBe(10);

    $last = $order->statusHistories()->get()->last();
    expect($last->getTranslation('note', 'en'))->toContain('Card payment not completed');

    // Idempotent: a second sweep finds nothing and never double-restores.
    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(0);
    expect($variant->fresh()->stock_qty)->toBe(10);
});

it('releases an abandoned pending card order past its window', function () {
    $variant = relVariant('F', stock: 8);
    $order = relCardOrder($variant, qty: 2, reservedUntil: now()->subMinute(), status: OrderPaymentStatus::Pending);

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(1);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock_qty)->toBe(10);
});

it('never sweeps a COD pending order regardless of age', function () {
    $variant = relVariant('G', stock: 6);
    $order = Order::create([
        'contact_name' => 'Nour',
        'contact_mobile' => '01000000000',
        'is_company' => false,
        'locale' => 'ar',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::Pending,
        'payment_method_code' => PaymentMethodCode::Cod->value,
        'subtotal' => 4000,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 4000,
        'vat_rate' => 14,
        'vat_amount' => 0,
        'reserved_until' => null, // COD carries no deadline
        'placed_at' => now(),
    ]);
    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'منتج', 'en' => 'Product'],
        'variant_label' => ['ar' => '', 'en' => ''],
        'sku' => $variant->sku,
        'unit_price' => 1000,
        'qty' => 4,
        'line_total' => 4000,
    ]);

    $this->travel(100)->days();

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(0);
    expect($order->fresh()->status)->toBe(OrderStatus::New)
        ->and($variant->fresh()->stock_qty)->toBe(6);

    $this->travelBack();
});

it('ignores a paid card order with its reservation cleared', function () {
    $variant = relVariant('H', stock: 5);
    $order = relCardOrder($variant, qty: 2, reservedUntil: null, status: OrderPaymentStatus::Paid);

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(0);
    expect($order->fresh()->status)->toBe(OrderStatus::New)
        ->and($variant->fresh()->stock_qty)->toBe(5);
});

it('sweeps a card order to cancellation after a declined webhook (full chain)', function () {
    config()->set('services.paymob.secret_key', 'sk_test');
    config()->set('services.paymob.public_key', 'pk_test');
    config()->set('services.paymob.integration_id_card', '55555');
    config()->set('services.paymob.hmac_secret', 'hmac_secret');

    $variant = relVariant('J', stock: 7); // 3 reserved out of 10
    $order = relCardOrder($variant, qty: 3, reservedUntil: now()->addMinutes(60), status: OrderPaymentStatus::Pending);
    $order->statusHistories()->create(['status' => OrderStatus::New->value, 'created_at' => now()]);

    // Paymob delivers a declined webhook: the order flips to failed but still
    // holds its stock and its pay-by deadline, awaiting a retry that never comes.
    $w = relFailedWebhook($order);
    $this->postJson('/payments/paymob/webhook?hmac='.$w['hmac'], ['type' => 'TRANSACTION', 'obj' => $w['obj']])
        ->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Failed)
        ->and($order->status)->toBe(OrderStatus::New)
        ->and($order->reserved_until)->not->toBeNull()
        ->and($variant->fresh()->stock_qty)->toBe(7);

    // The window passes with no retry: the sweeper cancels and restores the stock.
    $this->travel(61)->minutes();

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(1);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->reserved_until)->toBeNull()
        ->and($variant->fresh()->stock_qty)->toBe(10);

    $last = $order->statusHistories()->get()->last();
    expect($last->getTranslation('note', 'en'))->toContain('Card payment not completed');

    $this->travelBack();
});

it('soft-releases a coupon on auto-cancel, decrements used_count, and frees the customer', function () {
    $variant = relVariant('I', stock: 7);
    $customer = Customer::factory()->create();
    $coupon = Coupon::create([
        'code' => 'SWEEP10',
        'type' => CouponType::Fixed,
        'value' => 100,
        'min_order_total' => 0,
        'is_active' => true,
        'once_per_customer' => true,
        'usage_limit' => 1,
        'used_count' => 1,
    ]);

    $order = relCardOrder($variant, qty: 3, reservedUntil: now()->subMinute(), status: OrderPaymentStatus::Failed);
    $order->update(['customer_id' => $customer->id, 'coupon_id' => $coupon->id, 'coupon_code' => $coupon->code]);
    $redemption = CouponRedemption::create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'amount' => 100,
        'created_at' => now(),
    ]);

    // While redeemed the customer is blocked and the limit is exhausted.
    expect(app(Coupons::class)->resolve('SWEEP10', 1000, $customer->id)->error)->toBe('ui.coupon_limit');

    expect(app(ReleaseExpiredReservations::class)->handle())->toBe(1);

    expect($redemption->fresh()->released_at)->not->toBeNull()
        ->and((int) $coupon->fresh()->used_count)->toBe(0);

    // Coupon is usable again within its limit; the customer is no longer blocked.
    expect(app(Coupons::class)->resolve('SWEEP10', 1000, $customer->id)->error)->toBeNull();

    // Re-run does not double-decrement.
    app(ReleaseExpiredReservations::class)->handle();
    expect((int) $coupon->fresh()->used_count)->toBe(0);
});
