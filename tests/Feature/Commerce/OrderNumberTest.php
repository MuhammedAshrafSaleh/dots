<?php

use App\Models\Order;
use App\Services\Settings;
use Illuminate\Database\QueryException;

function makeOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'contact_name' => 'Ahmed',
        'contact_mobile' => '01000000000',
    ], $attributes));
}

it('generates the number as prefix + id once at creation, from the AUTO_INCREMENT base', function () {
    $order = makeOrder();

    expect($order->number)->toBe('DT-'.$order->id)
        ->and($order->id)->toBeGreaterThanOrEqual(10001)
        ->and($order->fresh()->number)->toBe('DT-'.$order->id);
});

it('uses the order_prefix setting when present', function () {
    app(Settings::class)->set('commerce.order_prefix', 'ORD-');

    expect(makeOrder()->number)->toStartWith('ORD-');
});

it('gives concurrent orders distinct, sequential numbers', function () {
    $a = makeOrder();
    $b = makeOrder();
    $c = makeOrder();

    $numbers = [$a->number, $b->number, $c->number];

    expect($numbers)->toHaveCount(3)
        ->and(array_unique($numbers))->toHaveCount(3)
        ->and($b->id)->toBe($a->id + 1)
        ->and($c->id)->toBe($b->id + 1);
});

it('rejects a duplicate number via the unique index', function () {
    $existing = makeOrder();

    $other = makeOrder();
    $other->number = $existing->number;
    $other->save();
})->throws(QueryException::class);
