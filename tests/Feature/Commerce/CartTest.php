<?php

use App\Enums\CouponType;
use App\Enums\ProductRelationType;
use App\Enums\ProductStatus;
use App\Livewire\Shop\CartDrawer;
use App\Livewire\Shop\CartPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use Livewire\Livewire;

function cartVariant(string $sku, float $price = 100, int $stock = 10, bool $active = true): ProductVariant
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
        'is_active' => $active,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

it('adds a variant and merges quantity on repeat adds', function () {
    $variant = cartVariant('A', 100);
    $cart = app(CartManager::class);

    $cart->add($variant->id, 2);
    $cart->add($variant->id, 3);

    expect($cart->itemCount())->toBe(5)
        ->and($cart->items())->toHaveCount(1)
        ->and($cart->subtotal())->toBe(500.0);
});

it('never lets a line quantity exceed 99', function () {
    $variant = cartVariant('B', 10);
    $cart = app(CartManager::class);

    $cart->add($variant->id, 90);
    $cart->add($variant->id, 50);

    expect($cart->itemCount())->toBe(99);
});

it('refuses to add a price-on-request / inactive variant', function () {
    $variant = cartVariant('C', 100, active: false);
    $cart = app(CartManager::class);

    $cart->add($variant->id, 1);

    expect($cart->itemCount())->toBe(0);
});

it('updates quantity and removes a line when set below one', function () {
    $variant = cartVariant('D', 100);
    $cart = app(CartManager::class);
    $cart->add($variant->id, 1);
    $item = $cart->items()->first();

    $cart->updateQty($item->id, 4);
    expect($cart->itemCount())->toBe(4);

    $cart->updateQty($item->id, 0);
    expect($cart->itemCount())->toBe(0);
});

it('computes a free delivery estimate once past the threshold', function () {
    DeliveryMethod::create([
        'name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'price' => 150,
        'free_over_amount' => 5000,
        'requires_address' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);
    $variant = cartVariant('E', 1000);
    $cart = app(CartManager::class);

    $cart->add($variant->id, 2); // 2000 — below threshold
    expect($cart->totals()->deliveryFee)->toBe(150.0)
        ->and($cart->totals()->deliveryFree)->toBeFalse();

    $cart->add($variant->id, 4); // 6000 — above threshold
    expect($cart->totals()->deliveryFree)->toBeTrue()
        ->and($cart->totals()->deliveryFee)->toBe(0.0);
});

it('applies a coupon discount into the totals and VAT stays on the net total', function () {
    Coupon::create(['code' => 'SAVE10', 'type' => CouponType::Percent, 'value' => 10, 'is_active' => true]);
    $variant = cartVariant('F', 1000);
    $cart = app(CartManager::class);
    $cart->add($variant->id, 1);

    $result = $cart->applyCoupon('save10');
    $totals = $cart->totals();

    expect($result->error)->toBeNull()
        ->and($totals->discount)->toBe(100.0)
        ->and($totals->couponCode)->toBe('SAVE10')
        ->and($totals->total)->toBe(900.0)
        ->and($totals->vat)->toBe(round(900 * 14 / 114, 2));
});

it('drops a session coupon that is no longer valid for the current subtotal', function () {
    Coupon::create(['code' => 'MIN500', 'type' => CouponType::Fixed, 'value' => 100, 'min_order_total' => 500, 'is_active' => true]);
    $variant = cartVariant('G', 100);
    $cart = app(CartManager::class);
    $cart->add($variant->id, 6); // 600 — meets the minimum

    expect($cart->applyCoupon('MIN500')->error)->toBeNull()
        ->and($cart->totals()->discount)->toBe(100.0);

    // Remove items so subtotal falls below the minimum — coupon auto-drops.
    $item = $cart->items()->first();
    $cart->updateQty($item->id, 1); // 100

    $totals = $cart->totals();
    expect($totals->discount)->toBe(0.0)
        ->and($totals->couponCode)->toBeNull();
});

it('suggests cart_suggestion related products not already in the cart', function () {
    $inCart = cartVariant('H', 100);
    $suggested = cartVariant('I', 200);

    DB::table('product_relations')->insert([
        'product_id' => $inCart->product_id,
        'related_id' => $suggested->product_id,
        'type' => ProductRelationType::CartSuggestion->value,
        'sort_order' => 0,
    ]);

    $cart = app(CartManager::class);
    $cart->add($inCart->id, 1);

    $suggestions = $cart->suggestions();
    expect($suggestions)->toHaveCount(1)
        ->and($suggestions->first()->id)->toBe($suggested->product_id);
});

it('opens the drawer and stores the item when a product card dispatches cart-add', function () {
    $variant = cartVariant('J', 100);

    Livewire::test(CartDrawer::class)
        ->call('addItem', $variant->id, 2)
        ->assertSet('open', true);

    expect(app(CartManager::class)->itemCount())->toBe(2);
});

it('surfaces a clear error when an invalid coupon is applied on the cart page', function () {
    Livewire::test(CartPage::class)
        ->set('couponCode', 'NOPE')
        ->call('applyCoupon')
        ->assertSet('couponError', 'ui.coupon_invalid');
});
