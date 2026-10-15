<?php

use App\Enums\CouponType;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Coupons;

/** @param  array<string, mixed>  $overrides */
function makeCoupon(string $code, array $overrides = []): Coupon
{
    return Coupon::create(array_merge([
        'code' => $code,
        'type' => CouponType::Fixed,
        'value' => 50,
        'min_order_total' => 0,
        'is_active' => true,
    ], $overrides));
}

it('rejects an unknown coupon code', function () {
    $result = app(Coupons::class)->resolve('NOPE', 1000);

    expect($result->error)->toBe('ui.coupon_invalid')
        ->and($result->coupon)->toBeNull()
        ->and($result->discount)->toBe(0.0);
});

it('matches coupon codes case-insensitively', function () {
    makeCoupon('SAVE50');

    $result = app(Coupons::class)->resolve('save50', 1000);

    expect($result->error)->toBeNull()
        ->and($result->coupon?->code)->toBe('SAVE50');
});

it('rejects an inactive coupon', function () {
    makeCoupon('OFF', ['is_active' => false]);

    expect(app(Coupons::class)->resolve('OFF', 1000)->error)->toBe('ui.coupon_invalid');
});

it('rejects a coupon that has not started yet', function () {
    makeCoupon('SOON', ['starts_at' => now()->addDay()]);

    expect(app(Coupons::class)->resolve('SOON', 1000)->error)->toBe('ui.coupon_invalid');
});

it('rejects an expired coupon by its end date', function () {
    makeCoupon('OLD', ['ends_at' => now()->subDay()]);

    expect(app(Coupons::class)->resolve('OLD', 1000)->error)->toBe('ui.coupon_expired');
});

it('rejects a coupon that reached its usage limit', function () {
    makeCoupon('MAXED', ['usage_limit' => 5, 'used_count' => 5]);

    expect(app(Coupons::class)->resolve('MAXED', 1000)->error)->toBe('ui.coupon_limit');
});

it('rejects a coupon below its minimum order total', function () {
    makeCoupon('BIG', ['min_order_total' => 2000]);

    $result = app(Coupons::class)->resolve('BIG', 1500);

    expect($result->error)->toBe('ui.coupon_min')
        ->and($result->errorParams)->toMatchArray(['amount' => '2,000']);
});

it('rejects a once-per-customer coupon the customer already redeemed', function () {
    $coupon = makeCoupon('ONCE', ['once_per_customer' => true]);
    $customer = Customer::factory()->create();

    $pm = PaymentMethod::create(['code' => PaymentMethodCode::Cod, 'name' => ['ar' => 'عند الاستلام', 'en' => 'COD']]);
    $order = Order::create([
        'customer_id' => $customer->id,
        'contact_name' => 'Sara',
        'contact_mobile' => '01111111111',
        'payment_method_id' => $pm->id,
        'status' => OrderStatus::Confirmed,
        'payment_status' => OrderPaymentStatus::Paid,
        'delivery_method_name' => ['ar' => 'توصيل', 'en' => 'Courier'],
    ]);
    CouponRedemption::create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'amount' => 50,
    ]);

    expect(app(Coupons::class)->resolve('ONCE', 1000, $customer->id)->error)->toBe('ui.coupon_used');
});

it('allows a once-per-customer coupon for a different / guest customer', function () {
    makeCoupon('ONCE2', ['once_per_customer' => true]);

    // Guest (no customer id) is never blocked at cart time.
    expect(app(Coupons::class)->resolve('ONCE2', 1000, null)->error)->toBeNull();
});

it('computes a fixed discount capped at the subtotal', function () {
    makeCoupon('FIX', ['type' => CouponType::Fixed, 'value' => 300]);

    expect(app(Coupons::class)->resolve('FIX', 1000)->discount)->toBe(300.0)
        ->and(app(Coupons::class)->resolve('FIX', 200)->discount)->toBe(200.0);
});

it('computes a percentage discount', function () {
    makeCoupon('PCT', ['type' => CouponType::Percent, 'value' => 10]);

    expect(app(Coupons::class)->resolve('PCT', 1000)->discount)->toBe(100.0);
});
