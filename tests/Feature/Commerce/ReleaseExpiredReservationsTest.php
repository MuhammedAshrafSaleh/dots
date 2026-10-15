<?php

use App\Actions\ReleaseExpiredReservations;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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
