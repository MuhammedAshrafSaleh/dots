<?php

use App\Enums\CouponType;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\ProductStatus;
use App\Livewire\Shop\CheckoutForm;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\DeliveryMethod;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function coVariant(string $sku, float $price = 1000, int $stock = 10): ProductVariant
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
        'min_price' => $price,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $sku.'-V',
        'price' => $price,
        'stock_qty' => $stock,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

function coStandardDelivery(): DeliveryMethod
{
    return DeliveryMethod::create([
        'name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'description' => ['ar' => 'توصيل', 'en' => 'Courier'],
        'icon' => 'truck',
        'price' => 150,
        'free_over_amount' => 5000,
        'requires_address' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function coPickup(): DeliveryMethod
{
    return DeliveryMethod::create([
        'name' => ['ar' => 'استلام', 'en' => 'Pickup'],
        'icon' => 'store',
        'price' => 0,
        'requires_address' => false,
        'is_active' => true,
        'sort_order' => 2,
    ]);
}

function coPayment(PaymentMethodCode $code, bool $active = true): PaymentMethod
{
    return PaymentMethod::create([
        'code' => $code,
        'name' => ['ar' => $code->value, 'en' => $code->value],
        'is_active' => $active,
        'sort_order' => 0,
    ]);
}

function coGovernorate(string $en = 'Cairo'): Governorate
{
    return Governorate::create(['name' => ['ar' => $en, 'en' => $en], 'sort_order' => 0, 'is_active' => true]);
}

/** @return array<string, mixed> */
function coForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mona Adel',
        'mobile' => '01000000000',
        'email' => 'mona@example.com',
        'city' => 'New Cairo',
        'street' => '12 Street',
    ], $overrides);
}

it('places a COD order: snapshots, history, cleared cart, decremented stock', function () {
    $variant = coVariant('A', 1000, stock: 5);
    $delivery = coStandardDelivery();
    coPayment(PaymentMethodCode::Cod);
    $gov = coGovernorate();
    app(CartManager::class)->add($variant->id, 2);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['governorateId' => $gov->id, 'deliveryMethodId' => $delivery->id, 'paymentCode' => 'cod']))
        ->call('place')
        ->assertRedirect();

    $order = Order::with(['items', 'address', 'statusHistories'])->first();
    expect($order)->not->toBeNull()
        ->and($order->number)->toStartWith('DT-')
        ->and($order->status)->toBe(OrderStatus::New)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Pending)
        ->and($order->payment_method_code)->toBe('cod')
        ->and($order->subtotal)->toBe('2000.00')
        ->and($order->delivery_fee)->toBe('150.00')
        ->and($order->total)->toBe('2150.00')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->sku)->toBe('A-V')
        ->and($order->items->first()->line_total)->toBe('2000.00')
        ->and($order->address->city)->toBe('New Cairo')
        ->and($order->statusHistories)->toHaveCount(1);

    expect($variant->fresh()->stock_qty)->toBe(3)
        ->and(app(CartManager::class)->itemCount())->toBe(0);
});

it('places an InstaPay order awaiting transfer with a reservation', function () {
    $variant = coVariant('B', 1000);
    $delivery = coStandardDelivery();
    coPayment(PaymentMethodCode::InstapayWhatsapp);
    $gov = coGovernorate();
    app(CartManager::class)->add($variant->id, 1);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['governorateId' => $gov->id, 'deliveryMethodId' => $delivery->id, 'paymentCode' => 'instapay_whatsapp']))
        ->call('place')
        ->assertRedirect();

    $order = Order::first();
    expect($order->payment_status)->toBe(OrderPaymentStatus::AwaitingTransfer)
        ->and($order->reserved_until)->not->toBeNull();
});

it('places a pickup order with no delivery address', function () {
    $variant = coVariant('C', 1000);
    $pickup = coPickup();
    coPayment(PaymentMethodCode::Cod);
    app(CartManager::class)->add($variant->id, 1);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['deliveryMethodId' => $pickup->id, 'paymentCode' => 'cod']))
        ->call('place')
        ->assertRedirect();

    $order = Order::with('address')->first();
    expect($order->delivery_fee)->toBe('0.00')
        ->and($order->address)->toBeNull();
});

it('rejects an order that exceeds available stock instead of overselling', function () {
    $variant = coVariant('D', 100, stock: 3);
    $delivery = coPickup();
    coPayment(PaymentMethodCode::Cod);
    // Force the cart above stock directly (cart add does not gate on stock).
    app(CartManager::class)->add($variant->id, 10);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['deliveryMethodId' => $delivery->id, 'paymentCode' => 'cod']))
        ->call('place')
        ->assertHasErrors('name')
        ->assertNoRedirect();

    // The whole transaction rolls back: no order, no sale, stock untouched
    // (never negative, never oversold).
    expect(Order::count())->toBe(0)
        ->and($variant->fresh()->stock_qty)->toBe(3);
});

it('redeems a coupon and increments its used count', function () {
    Coupon::create(['code' => 'SAVE10', 'type' => CouponType::Percent, 'value' => 10, 'is_active' => true]);
    $variant = coVariant('E', 1000);
    $delivery = coPickup();
    coPayment(PaymentMethodCode::Cod);
    $cart = app(CartManager::class);
    $cart->add($variant->id, 1);
    $cart->applyCoupon('SAVE10');

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['deliveryMethodId' => $delivery->id, 'paymentCode' => 'cod']))
        ->call('place')
        ->assertRedirect();

    $order = Order::first();
    expect($order->discount_total)->toBe('100.00')
        ->and($order->coupon_code)->toBe('SAVE10')
        ->and($order->total)->toBe('900.00');

    expect(Coupon::where('code', 'SAVE10')->first()->used_count)->toBe(1)
        ->and(CouponRedemption::count())->toBe(1);
});

it('rejects an invalid Egyptian mobile number', function () {
    $variant = coVariant('F', 1000);
    $delivery = coPickup();
    coPayment(PaymentMethodCode::Cod);
    app(CartManager::class)->add($variant->id, 1);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['mobile' => '0123', 'deliveryMethodId' => $delivery->id, 'paymentCode' => 'cod']))
        ->call('place')
        ->assertHasErrors(['mobile'])
        ->assertNoRedirect();

    expect(Order::count())->toBe(0);
});

it('requires a tax ID for company orders', function () {
    $variant = coVariant('G', 1000);
    $delivery = coPickup();
    coPayment(PaymentMethodCode::Cod);
    app(CartManager::class)->add($variant->id, 1);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['isCompany' => true, 'deliveryMethodId' => $delivery->id, 'paymentCode' => 'cod']))
        ->call('place')
        ->assertHasErrors(['companyTaxId']);
});

it('hides the card method until Paymob is configured', function () {
    app()->setLocale('en');
    $variant = coVariant('H', 1000);
    coPickup();
    coPayment(PaymentMethodCode::Cod);
    PaymentMethod::create([
        'code' => PaymentMethodCode::Card,
        'name' => ['ar' => 'بطاقة ائتمان', 'en' => 'Credit card'],
        'is_active' => true,
        'sort_order' => 1,
    ]);
    app(CartManager::class)->add($variant->id, 1);

    config()->set('services.paymob.secret_key', null);
    Livewire::test(CheckoutForm::class)->assertDontSee('Credit card');

    config()->set('services.paymob.secret_key', 'sk_test_123');
    Livewire::test(CheckoutForm::class)->assertSee('Credit card');
});

it('sends a card order to the Paymob hosted checkout', function () {
    config()->set('services.paymob.base_url', 'https://pay.test');
    config()->set('services.paymob.secret_key', 'sk_test');
    config()->set('services.paymob.public_key', 'pk_test');
    config()->set('services.paymob.integration_id_card', '55555');
    config()->set('services.paymob.hmac_secret', 'hmac');
    Http::fake(['pay.test/v1/intention/*' => Http::response(['id' => 'int_7', 'client_secret' => 'cs_1'], 200)]);

    $variant = coVariant('CARD', 1000);
    $pickup = coPickup();
    coPayment(PaymentMethodCode::Card);
    app(CartManager::class)->add($variant->id, 1);

    Livewire::test(CheckoutForm::class)
        ->set(coForm(['deliveryMethodId' => $pickup->id, 'paymentCode' => 'card']))
        ->call('place')
        ->assertRedirect('https://pay.test/unifiedcheckout/?publicKey=pk_test&clientSecret=cs_1');

    $order = Order::first();
    expect($order->payment_method_code)->toBe('card')
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Pending)
        ->and($order->payments()->count())->toBe(1);
});

it('only offers delivery methods serving the chosen governorate', function () {
    app()->setLocale('en');
    $cairo = coGovernorate('Cairo');
    $alex = coGovernorate('Alexandria');
    $everywhere = coStandardDelivery();
    $cairoOnly = DeliveryMethod::create([
        'name' => ['ar' => 'سريع', 'en' => 'Express'], 'icon' => 'zap',
        'price' => 300, 'requires_address' => true, 'is_active' => true, 'sort_order' => 1,
    ]);
    $cairoOnly->governorates()->sync([$cairo->id]);
    coPayment(PaymentMethodCode::Cod);
    $variant = coVariant('I', 1000);
    app(CartManager::class)->add($variant->id, 1);

    Livewire::test(CheckoutForm::class)
        ->set('governorateId', $alex->id)
        ->assertSee('Standard')
        ->assertDontSee('Express');
});
