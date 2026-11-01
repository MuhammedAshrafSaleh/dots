<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\PaymentStatus;
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
use Illuminate\Support\Facades\Http;
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

// ---------------------------------------------------------------------------
// PAY.4 — failure reason display
// ---------------------------------------------------------------------------

it('shows a curated reason for a known failure code', function () {
    app()->setLocale('en');

    $order = pfOrder();
    Payment::create([
        'order_id' => $order->id,
        'transaction_id' => 'txn-known',
        'gateway' => 'paymob',
        'amount' => $order->total,
        'currency' => 'EGP',
        'status' => PaymentStatus::Failed,
        'failure_code' => '51', // insufficient funds
        'failure_message' => 'Raw gateway text that must never appear',
        'hmac_verified' => true,
    ]);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->assertSee('enough balance for this transaction')
        ->assertDontSee('Raw gateway text that must never appear');
});

it('shows the generic reason for an unknown failure code', function () {
    app()->setLocale('en');

    $order = pfOrder();
    Payment::create([
        'order_id' => $order->id,
        'transaction_id' => 'txn-unknown',
        'gateway' => 'paymob',
        'amount' => $order->total,
        'currency' => 'EGP',
        'status' => PaymentStatus::Failed,
        'failure_code' => 'SOME_UNKNOWN_CODE',
        'failure_message' => 'Raw gateway text that must never appear',
        'hmac_verified' => true,
    ]);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->assertSee('go through. Try again')
        ->assertDontSee('Raw gateway text that must never appear');
});

it('shows no reason line when there is no failed payment', function () {
    app()->setLocale('en');

    $order = pfOrder();
    session(['orders.placed' => [$order->id]]);

    // No Payment row at all — gateway threw before recording anything.
    $html = Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->html();

    expect($html)->not->toContain('data-reason');
});

// ---------------------------------------------------------------------------
// Translatable snapshot rendering
// ---------------------------------------------------------------------------

it('renders the Arabic product name in the expired order summary without raw JSON', function () {
    app()->setLocale('ar');

    $v = pfVariant('AR-NAME', stock: 5, name: ['ar' => 'نجفة كريستال', 'en' => 'Crystal Pendant']);
    $order = pfOrderFor([[$v, 1]]);
    session(['orders.placed' => [$order->id]]);

    $html = Livewire::test(PaymentFailed::class, ['number' => $order->number])->html();

    expect($html)
        ->toContain('نجفة كريستال')
        ->not->toContain('{"ar"');
});

it('renders the English product name in the expired order summary without raw JSON', function () {
    app()->setLocale('en');

    $v = pfVariant('EN-NAME', stock: 5, name: ['ar' => 'نجفة كريستال', 'en' => 'Crystal Pendant']);
    $order = pfOrderFor([[$v, 1]]);
    session(['orders.placed' => [$order->id]]);

    $html = Livewire::test(PaymentFailed::class, ['number' => $order->number])->html();

    expect($html)
        ->toContain('Crystal Pendant')
        ->not->toContain('{"en"');
});

it('cart notice for a reduced item shows the translated name without raw JSON', function () {
    app()->setLocale('ar');

    $v = pfVariant('AR-REDUCED', stock: 1, name: ['ar' => 'كشاف مدمج', 'en' => 'Recessed Downlight']);
    $order = pfOrderFor([[$v, 3]]); // qty 3, stock 1 → reduced notice
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])->call('reAddToCart');

    $notices = implode(' ', session('cart_notices', []));
    expect($notices)
        ->toContain('كشاف مدمج')
        ->not->toContain('{');
});

// ---------------------------------------------------------------------------
// PAY.9 — a settled payment (paid/refunded) is terminal; no branch may act.
// ---------------------------------------------------------------------------

/** A settled card order with the given payment_status (defaults to paid). */
function pfPaidOrder(OrderPaymentStatus $status = OrderPaymentStatus::Paid): Order
{
    $order = pfOrder(qty: 2, stock: 5);
    $order->update(['payment_status' => $status, 'reserved_until' => null]);

    return $order;
}

it('treats a paid order as settled on mount, not expired', function () {
    $order = pfPaidOrder();
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->assertSet('settled', true)
        ->assertSet('expired', false);
});

it('treats a refunded order as settled on mount', function () {
    $order = pfPaidOrder(OrderPaymentStatus::Refunded);
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->assertSet('settled', true);
});

it('does not retry card on a paid order: no intention started, no payment row', function () {
    Http::fake(); // any outbound gateway call would be recorded here

    $order = pfPaidOrder();
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->set('option', 'retry')
        ->call('proceed')
        ->assertSet('settled', true)
        ->assertNoRedirect();

    Http::assertNothingSent();
    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->payment_method_code)->toBe(PaymentMethodCode::Card->value)
        ->and(Payment::where('order_id', $order->id)->count())->toBe(0);
});

it('does not switch a paid order to COD', function () {
    PaymentMethod::create(['code' => PaymentMethodCode::Cod, 'name' => ['ar' => 'عند الاستلام', 'en' => 'COD'], 'is_active' => true]);

    $order = pfPaidOrder();
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->set('option', 'cod')
        ->call('proceed')
        ->assertSet('settled', true);

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->payment_method_code)->toBe(PaymentMethodCode::Card->value) // unchanged
        ->and(Payment::where('order_id', $order->id)->count())->toBe(0);
});

it('does not switch a paid order to InstaPay', function () {
    PaymentMethod::create(['code' => PaymentMethodCode::InstapayWhatsapp, 'name' => ['ar' => 'إنستاباي', 'en' => 'InstaPay'], 'is_active' => true]);

    $order = pfPaidOrder();
    session(['orders.placed' => [$order->id]]);

    Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->set('option', 'instapay')
        ->call('proceed')
        ->assertSet('settled', true);

    $order->refresh();
    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->payment_method_code)->toBe(PaymentMethodCode::Card->value) // unchanged
        ->and(Payment::where('order_id', $order->id)->count())->toBe(0);
});

it('refuses to act when the order becomes paid between page load and the click (race)', function () {
    Http::fake();

    $order = pfOrder(); // failed + actionable at mount
    session(['orders.placed' => [$order->id]]);

    $component = Livewire::test(PaymentFailed::class, ['number' => $order->number])
        ->assertSet('settled', false);

    // A webhook settles the order after the page rendered, before the click.
    $order->update(['payment_status' => OrderPaymentStatus::Paid, 'reserved_until' => null]);

    $component->set('option', 'retry')
        ->call('proceed')
        ->assertSet('settled', true)
        ->assertNoRedirect();

    Http::assertNothingSent();
    expect(Payment::where('order_id', $order->id)->count())->toBe(0)
        ->and($order->fresh()->payment_method_code)->toBe(PaymentMethodCode::Card->value);
});
