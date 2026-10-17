<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Livewire\Shop\PaymentFailed;
use App\Livewire\Shop\TrackOrder;
use App\Models\Order;
use App\Models\PaymentMethod;
use Livewire\Livewire;

function trackOrder(array $overrides = []): Order
{
    $order = Order::create(array_merge([
        'contact_name' => 'Mona Adel',
        'contact_mobile' => '01000000000',
        'contact_email' => 'mona@example.com',
        'status' => OrderStatus::Shipped,
        'payment_status' => OrderPaymentStatus::Paid,
        'payment_method_code' => 'cod',
        'delivery_method_name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'subtotal' => 1000,
        'discount_total' => 0,
        'delivery_fee' => 150,
        'total' => 1150,
        'vat_rate' => 14,
        'vat_amount' => 141.23,
        'placed_at' => now(),
    ], $overrides));

    $order->items()->create([
        'product_name' => ['ar' => 'منتج', 'en' => 'Item'],
        'sku' => 'SKU-1',
        'unit_price' => 1000,
        'qty' => 1,
        'line_total' => 1000,
    ]);

    foreach ([OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped] as $status) {
        $order->statusHistories()->create(['status' => $status->value, 'created_at' => now()]);
    }

    return $order->refresh();
}

it('finds an order only when both the number and the mobile match', function () {
    $order = trackOrder(['contact_mobile' => '01000000000']);

    Livewire::test(TrackOrder::class)
        ->set('number', $order->number)
        ->set('mobile', '01000000000')
        ->call('track')
        ->assertSet('foundOrderId', $order->id)
        ->assertSee($order->number);
});

it('rejects the right number with the wrong mobile', function () {
    $order = trackOrder(['contact_mobile' => '01000000000']);

    Livewire::test(TrackOrder::class)
        ->set('number', $order->number)
        ->set('mobile', '01099999999')
        ->call('track')
        ->assertSet('foundOrderId', null)
        ->assertSee(__('ui.track_no_match_title'));
});

it('does not leak one order to another order’s mobile', function () {
    $a = trackOrder(['contact_mobile' => '01000000000']);
    $b = trackOrder(['contact_mobile' => '01111111111']);

    // A's number with B's mobile must not resolve to any order.
    Livewire::test(TrackOrder::class)
        ->set('number', $a->number)
        ->set('mobile', '01111111111')
        ->call('track')
        ->assertSet('foundOrderId', null);

    expect($a->id)->not->toBe($b->id);
});

it('normalises spaces in the entered mobile before matching', function () {
    $order = trackOrder(['contact_mobile' => '01000000000']);

    Livewire::test(TrackOrder::class)
        ->set('number', $order->number)
        ->set('mobile', '010 0000 0000')
        ->call('track')
        ->assertSet('foundOrderId', $order->id);
});

it('never mentions SMS on the track screen', function () {
    Livewire::test(TrackOrder::class)->assertDontSee('SMS');
});

it('shows the order confirmation to the guest who placed it in session', function () {
    $order = trackOrder();

    $this->withSession(['orders.placed' => [$order->id]])
        ->get(route('order.confirmation', ['ar', $order->number]))
        ->assertOk()
        ->assertSee($order->number)
        ->assertDontSee('SMS');
});

it('404s the confirmation for a visitor who did not place the order', function () {
    $order = trackOrder();

    $this->get(route('order.confirmation', ['ar', $order->number]))
        ->assertNotFound();
});

it('lets the buyer switch a failed payment to cash on delivery', function () {
    $cod = PaymentMethod::create([
        'code' => PaymentMethodCode::Cod,
        'name' => ['ar' => 'عند الاستلام', 'en' => 'COD'],
        'is_active' => true,
        'sort_order' => 0,
    ]);
    $order = trackOrder(['payment_status' => OrderPaymentStatus::Failed, 'payment_method_code' => 'card']);

    session()->push('orders.placed', $order->id);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->set('option', 'cod')
        ->call('proceed')
        ->assertRedirect(route('order.confirmation', ['ar', $order->number]));

    $order->refresh();
    expect($order->payment_method_id)->toBe($cod->id)
        ->and($order->payment_method_code)->toBe('cod')
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Pending)
        ->and($order->statusHistories()->count())->toBe(5); // 4 original + 1 "payment method changed"
});

it('404s the payment-failed screen for an unrelated visitor', function () {
    $order = trackOrder(['payment_status' => OrderPaymentStatus::Failed]);

    $this->get(route('order.payment-failed', ['ar', $order->number]))
        ->assertNotFound();
});
