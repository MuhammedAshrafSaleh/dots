<?php

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * T8.2 — Real-browser E2E for guest-order linking and cart merging.
 *
 * Guest-order linking: a new account attaches only orders where BOTH
 * contact_mobile AND contact_email match (PRD §9).
 *
 * Cart merging: a guest cart is folded into the customer cart on login
 * and on register (quantities add, capped at 99). Tests chain everything
 * via navigate() on the same page so the session cookie is preserved.
 */

/** Minimal Brand + Category (3-level) + Product + Variant for cart tests. */
function cartTestProduct(string $suffix): ProductVariant
{
    $brand = Brand::create(['name' => 'CartBrand'.$suffix, 'slug' => 'cart-brand'.$suffix]);
    $root = Category::create([
        'level' => 1, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'تصنيف'.$suffix, 'en' => 'Root'.$suffix],
        'slug' => ['ar' => 'تصنيف'.$suffix, 'en' => 'root'.$suffix],
    ]);
    $group = Category::create([
        'level' => 2, 'parent_id' => $root->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'مجموعة'.$suffix, 'en' => 'Group'.$suffix],
        'slug' => ['ar' => 'مجموعة'.$suffix, 'en' => 'grp'.$suffix],
    ]);
    $leaf = Category::create([
        'level' => 3, 'parent_id' => $group->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'ورقة'.$suffix, 'en' => 'Leaf'.$suffix],
        'slug' => ['ar' => 'ورقة'.$suffix, 'en' => 'lf'.$suffix],
    ]);

    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $leaf->id,
        'name' => ['ar' => 'منتج اختبار'.$suffix, 'en' => 'CartProd'.$suffix],
        'slug' => ['ar' => 'منتج-اختبار'.$suffix, 'en' => 'cart-prod'.$suffix],
        'sku' => 'CART-SKU'.$suffix,
        'status' => 'active',
        'published_at' => now()->subDay(),
    ]);

    return $product->variants()->create([
        'sku' => 'CART-VAR'.$suffix,
        'price' => 250.00,
        'is_default' => true,
        'is_active' => true,
        'stock_qty' => 50,    // > 0 so stock_status is not 'order' and Add to Cart is enabled
    ]);
}

// ─── Guest order linking ───────────────────────────────────────────────────────

it('links a guest order when mobile AND email both match the new customer', function () {
    Order::create([
        'contact_name' => 'Guest',
        'contact_mobile' => '01011220001',
        'contact_email' => 'link-both@dots.test',
        'status' => 'new',
        'payment_status' => 'pending',
        'locale' => 'en',
    ]);

    visit('/en/register')
        ->fill('[wire\\:model="name"]', 'Link User')
        ->fill('[wire\\:model="mobile"]', '01011220001')
        ->fill('[wire\\:model="email"]', 'link-both@dots.test')
        ->fill('[wire\\:model="password"]', 'password1')
        ->check('[wire\\:model="terms"]')
        ->press('Create account')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();

    $customer = Customer::where('email', 'link-both@dots.test')->firstOrFail();
    expect(Order::where('contact_mobile', '01011220001')->value('customer_id'))
        ->toBe($customer->id);
});

it('does NOT link a guest order when only the mobile matches', function () {
    Order::create([
        'contact_name' => 'Mobile Only',
        'contact_mobile' => '01011220002',
        'contact_email' => 'different@example.com',   // will NOT match the registration email
        'status' => 'new',
        'payment_status' => 'pending',
        'locale' => 'en',
    ]);

    visit('/en/register')
        ->fill('[wire\\:model="name"]', 'Mobile Only User')
        ->fill('[wire\\:model="mobile"]', '01011220002')
        ->fill('[wire\\:model="email"]', 'mobile-only@dots.test')
        ->fill('[wire\\:model="password"]', 'password1')
        ->check('[wire\\:model="terms"]')
        ->press('Create account')
        ->assertNoJavaScriptErrors();

    // Order still has no customer_id
    expect(Order::where('contact_mobile', '01011220002')->value('customer_id'))->toBeNull();
});

it('does not steal an order that already belongs to another customer', function () {
    $owner = Customer::factory()->create(['mobile' => '01011220003', 'email' => 'real-owner@dots.test']);
    $order = Order::create([
        'customer_id' => $owner->id,
        'contact_name' => 'Real Owner',
        'contact_mobile' => '01011220003',
        'contact_email' => 'real-owner@dots.test',
        'status' => 'new',
        'payment_status' => 'pending',
        'locale' => 'en',
    ]);

    visit('/en/register')
        ->fill('[wire\\:model="name"]', 'Attacker')
        ->fill('[wire\\:model="mobile"]', '01011220004')
        ->fill('[wire\\:model="email"]', 'attacker@dots.test')
        ->fill('[wire\\:model="password"]', 'password1')
        ->check('[wire\\:model="terms"]')
        ->press('Create account')
        ->assertNoJavaScriptErrors();

    expect(Order::find($order->id)?->customer_id)->toBe($owner->id);
});

// ─── Cart merging ─────────────────────────────────────────────────────────────

it('merges guest cart into customer cart on login', function () {
    $variant = cartTestProduct('-login');
    $customer = Customer::factory()->create(['email' => 'cart-login@dots.test']);

    // All steps in ONE chain to preserve the browser session cookie.
    visit('/en/products/cart-prod-login')
        ->press('Add to cart')
        ->navigate('/en/login')
        ->fill('[wire\\:model="identifier"]', 'cart-login@dots.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();

    $cart = Cart::where('customer_id', $customer->id)->first();
    expect($cart)->not->toBeNull();
    expect(CartItem::where('cart_id', $cart->id)
        ->where('product_variant_id', $variant->id)
        ->exists())->toBeTrue();
});

it('merges guest cart into the new account cart on register', function () {
    $variant = cartTestProduct('-reg');

    visit('/en/products/cart-prod-reg')
        ->press('Add to cart')
        ->navigate('/en/register')
        ->fill('[wire\\:model="name"]', 'Cart Reg User')
        ->fill('[wire\\:model="mobile"]', '01011220010')
        ->fill('[wire\\:model="email"]', 'cart-reg@dots.test')
        ->fill('[wire\\:model="password"]', 'password1')
        ->check('[wire\\:model="terms"]')
        ->press('Create account')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();

    $customer = Customer::where('email', 'cart-reg@dots.test')->firstOrFail();
    $cart = Cart::where('customer_id', $customer->id)->first();
    expect($cart)->not->toBeNull();
    expect(CartItem::where('cart_id', $cart->id)
        ->where('product_variant_id', $variant->id)
        ->exists())->toBeTrue();
});
