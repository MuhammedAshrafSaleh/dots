<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Livewire\Shop\PaymentFailed;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Settings;
use Livewire\Livewire;

function instapayOrder(array $overrides = []): Order
{
    $pm = PaymentMethod::firstOrCreate(
        ['code' => PaymentMethodCode::InstapayWhatsapp->value],
        ['name' => ['ar' => 'إنستاباي', 'en' => 'InstaPay'], 'is_active' => true, 'sort_order' => 0],
    );

    $order = Order::create(array_merge([
        'contact_name' => 'Layla Hassan',
        'contact_mobile' => '01011111111',
        'contact_email' => 'layla@example.com',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::AwaitingTransfer,
        'payment_method_code' => PaymentMethodCode::InstapayWhatsapp->value,
        'payment_method_id' => $pm->id,
        'delivery_method_name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'subtotal' => 500,
        'discount_total' => 0,
        'delivery_fee' => 50,
        'total' => 550,
        'vat_rate' => 14,
        'vat_amount' => 67.61,
        'placed_at' => now(),
        'reserved_until' => now()->addHours(24),
    ], $overrides));

    $order->items()->create([
        'product_name' => ['ar' => 'منتج', 'en' => 'Item'],
        'sku' => 'SKU-IP',
        'unit_price' => 500,
        'qty' => 1,
        'line_total' => 500,
    ]);

    return $order->refresh();
}

it('shows instapay address, amount, order number and deadline when address is set', function () {
    app(Settings::class)->set('commerce.instapay_address', 'dots@instapay.eg');

    $order = instapayOrder();

    $this->withSession(['orders.placed' => [$order->id]])
        ->get(route('order.confirmation', ['en', $order->number]))
        ->assertOk()
        ->assertSee('dots@instapay.eg')
        ->assertSee($order->number)
        ->assertSee('550.00')
        ->assertSee($order->reserved_until->format('Y'));
});

it('shows fallback copy and no address when instapay address is blank', function () {
    app(Settings::class)->set('commerce.instapay_address', '');

    $order = instapayOrder();

    $this->withSession(['orders.placed' => [$order->id]])
        ->get(route('order.confirmation', ['en', $order->number]))
        ->assertOk()
        ->assertSee('data-instapay-address-fallback', false)
        ->assertSee("We'll send the InstaPay address on WhatsApp shortly.")
        ->assertDontSee('data-instapay-address>', false);
});

it('does not show deadline row when reserved_until is null', function () {
    app(Settings::class)->set('commerce.instapay_address', 'dots@instapay.eg');

    $order = instapayOrder(['reserved_until' => null]);

    $this->withSession(['orders.placed' => [$order->id]])
        ->get(route('order.confirmation', ['en', $order->number]))
        ->assertOk()
        ->assertDontSee('data-instapay-deadline', false);
});

it('returns 404 for an unauthorised viewer and address string is absent', function () {
    app(Settings::class)->set('commerce.instapay_address', 'dots@instapay.eg');

    $order = instapayOrder();

    $this->get(route('order.confirmation', ['en', $order->number]))
        ->assertNotFound()
        ->assertDontSee('dots@instapay.eg');
});

it('shows instapay details after switching from payment-failed to instapay', function () {
    app(Settings::class)->set('commerce.instapay_address', 'dots@instapay.eg');

    $pm = PaymentMethod::firstOrCreate(
        ['code' => PaymentMethodCode::InstapayWhatsapp->value],
        ['name' => ['ar' => 'إنستاباي', 'en' => 'InstaPay'], 'is_active' => true, 'sort_order' => 0],
    );

    $cardPm = PaymentMethod::firstOrCreate(
        ['code' => PaymentMethodCode::Card->value],
        ['name' => ['ar' => 'بطاقة', 'en' => 'Card'], 'is_active' => true, 'sort_order' => 1],
    );

    $order = Order::create([
        'contact_name' => 'Layla Hassan',
        'contact_mobile' => '01011111111',
        'contact_email' => 'layla@example.com',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::Failed,
        'payment_method_code' => PaymentMethodCode::Card->value,
        'payment_method_id' => $cardPm->id,
        'delivery_method_name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'subtotal' => 500,
        'discount_total' => 0,
        'delivery_fee' => 50,
        'total' => 550,
        'vat_rate' => 14,
        'vat_amount' => 67.61,
        'placed_at' => now(),
        'reserved_until' => now()->addHour(),
    ]);
    $order->items()->create([
        'product_name' => ['ar' => 'منتج', 'en' => 'Item'],
        'sku' => 'SKU-CF',
        'unit_price' => 500,
        'qty' => 1,
        'line_total' => 500,
    ]);

    session()->push('orders.placed', $order->id);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->set('option', 'instapay')
        ->call('proceed');

    $order->refresh();

    $this->withSession(['orders.placed' => [$order->id]])
        ->get(route('order.confirmation', ['en', $order->number]))
        ->assertOk()
        ->assertSee('dots@instapay.eg')
        ->assertSee($order->number);
});
