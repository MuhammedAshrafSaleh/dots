<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\ProductStatus;
use App\Livewire\Shop\PaymentFailed;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use Livewire\Livewire;

/** A card order (failed) holding `qty` units of a fresh variant with `stock`. */
function pfOrder(int $qty = 2, int $stock = 5): Order
{
    $brand = Brand::create(['name' => 'PFB', 'slug' => 'pf-b']);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'C'],
        'slug' => ['ar' => 'ق-pf', 'en' => 'c-pf'],
    ]);
    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'منتج', 'en' => 'Lamp'],
        'slug' => ['ar' => 'م-pf', 'en' => 'p-pf'],
        'sku' => 'PF1',
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'price_on_request' => false,
        'min_price' => 1000,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'PF1-V',
        'price' => 1000,
        'stock_qty' => $stock,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);

    $order = Order::create([
        'contact_name' => 'Karim',
        'contact_mobile' => '01000000000',
        'is_company' => false,
        'locale' => 'en',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::Failed,
        'payment_method_code' => PaymentMethodCode::Card->value,
        'subtotal' => 1000 * $qty,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 1000 * $qty,
        'vat_rate' => 14,
        'vat_amount' => 0,
        'reserved_until' => now()->subMinute(),
        'placed_at' => now(),
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'منتج', 'en' => 'Lamp'],
        'variant_label' => ['ar' => '', 'en' => ''],
        'sku' => $variant->sku,
        'unit_price' => 1000,
        'qty' => $qty,
        'line_total' => 1000 * $qty,
    ]);

    return $order;
}

it('mutates nothing and shows the expired state when picking COD on an already-cancelled order', function () {
    $order = pfOrder(qty: 2, stock: 5);
    $variant = $order->items->first()->productVariant;
    PaymentMethod::create(['code' => PaymentMethodCode::Cod, 'name' => ['ar' => 'عند الاستلام', 'en' => 'COD'], 'is_active' => true]);

    // The sweeper cancelled the order while the customer sat on the page.
    $order->update(['status' => OrderStatus::Cancelled, 'reserved_until' => null]);

    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->set('option', 'cod')
        ->call('proceed')
        ->assertSet('expired', true);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->payment_method_code)->toBe(PaymentMethodCode::Card->value) // unchanged
        ->and(Payment::where('order_id', $order->id)->count())->toBe(0)
        ->and($variant->fresh()->stock_qty)->toBe(5); // no stock movement
});

it('re-adds the cancelled order items to the cart clamped to current stock', function () {
    $order = pfOrder(qty: 4, stock: 2); // only 2 left now
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->call('reAddToCart');

    $count = app(CartManager::class)->itemCount();
    expect($count)->toBe(2); // clamped from 4 down to current stock
});

it('marks the order expired on mount when it is already cancelled', function () {
    $order = pfOrder();
    $order->update(['status' => OrderStatus::Cancelled]);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->assertSet('expired', true);
});

/**
 * A variant with its product scaffolding, product relation pre-loaded so the
 * snapshot name is readable without a (blocked) lazy load.
 *
 * @param  array<string, string>  $name
 */
function pfVariant(string $sku, int $stock, bool $active = true, array $name = ['ar' => 'منتج', 'en' => 'Item']): ProductVariant
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
        'name' => $name,
        'slug' => ['ar' => 'م-'.$sku, 'en' => 'p-'.strtolower($sku)],
        'sku' => $sku,
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'price_on_request' => false,
        'min_price' => 1000,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $sku.'-V',
        'price' => 1000,
        'stock_qty' => $stock,
        'is_active' => $active,
        'is_default' => true,
        'sort_order' => 0,
    ]);
    $variant->setRelation('product', $product);

    return $variant;
}

/**
 * A cancelled card order with the given [variant, qty] lines, each snapshotting
 * the variant's current product name.
 *
 * @param  array<int, array{0: ProductVariant, 1: int}>  $lines
 */
function pfOrderFor(array $lines): Order
{
    $order = Order::create([
        'contact_name' => 'Karim',
        'contact_mobile' => '01000000000',
        'is_company' => false,
        'locale' => 'en',
        'status' => OrderStatus::Cancelled,
        'payment_status' => OrderPaymentStatus::Failed,
        'payment_method_code' => PaymentMethodCode::Card->value,
        'subtotal' => 1000,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 1000,
        'vat_rate' => 14,
        'vat_amount' => 0,
        'reserved_until' => null,
        'placed_at' => now(),
    ]);

    foreach ($lines as [$variant, $qty]) {
        $order->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->getTranslations('name'),
            'variant_label' => ['ar' => '', 'en' => ''],
            'sku' => $variant->sku,
            'unit_price' => 1000,
            'qty' => $qty,
            'line_total' => 1000 * $qty,
        ]);
    }

    return $order;
}

it('drops an inactive line, keeps the rest, and names the dropped item', function () {
    app()->setLocale('en');

    $ok = pfVariant('OK', stock: 5, name: ['ar' => 'كشاف', 'en' => 'Track light']);
    $gone = pfVariant('GONE', stock: 5, active: false, name: ['ar' => 'سبوت', 'en' => 'Spotlight']);
    $order = pfOrderFor([[$ok, 2], [$gone, 1]]);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->call('reAddToCart')
        ->assertRedirect(route('cart', 'en'));

    expect(app(CartManager::class)->itemCount())->toBe(2); // only the available line

    $msg = implode(' ', session('cart_notices'));
    expect($msg)->toContain('Spotlight')
        ->and($msg)->toContain('no longer available')
        ->and($msg)->not->toContain('None of the items');
});

it('lands on an empty cart with a clear message when nothing can be restored', function () {
    app()->setLocale('en');

    $a = pfVariant('A', stock: 0, name: ['ar' => 'لمبة', 'en' => 'Lamp A']);
    $b = pfVariant('B', stock: 0, name: ['ar' => 'لمبة', 'en' => 'Lamp B']);
    $order = pfOrderFor([[$a, 1], [$b, 2]]);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])->call('reAddToCart');

    expect(app(CartManager::class)->itemCount())->toBe(0);
    expect(implode(' ', session('cart_notices')))->toContain('None of the items could be added back');
});

it('reports reduced quantities with requested and actual counts', function () {
    app()->setLocale('en');

    $v = pfVariant('RED', stock: 1, name: ['ar' => 'نجفة', 'en' => 'Pendant']);
    $order = pfOrderFor([[$v, 3]]);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])->call('reAddToCart');

    expect(app(CartManager::class)->itemCount())->toBe(1); // clamped 3 -> 1

    $msg = implode(' ', session('cart_notices'));
    expect($msg)->toContain('Pendant')
        ->and($msg)->toContain('(3 → 1)')
        ->and($msg)->toContain('lowered some quantities');
});

it('renders flashed cart notices on the cart page', function () {
    $this->withSession(['cart_notices' => ['Heads up: an item was left out of your cart.']])
        ->get(route('cart', 'en'))
        ->assertOk()
        ->assertSee('Heads up: an item was left out of your cart.');
});
