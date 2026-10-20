<?php

use App\Actions\LinkGuestOrders;
use App\Enums\ProductStatus;
use App\Livewire\Account\Auth\Login;
use App\Livewire\Account\Auth\Register;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

function guestOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'contact_name' => 'Guest Buyer',
        'contact_mobile' => '01012345678',
        'contact_email' => 'buyer@example.com',
    ], $attributes));
}

function linkVariant(string $sku): ProductVariant
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
        'min_price' => 100,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $sku.'-V',
        'price' => 100,
        'stock_qty' => 10,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

/* ---------- linking guest orders ---------- */

it('attaches a guest order when both mobile and email match', function () {
    $order = guestOrder(['contact_mobile' => '01099998888', 'contact_email' => 'match@example.com']);

    $customer = Customer::factory()->create([
        'mobile' => '01099998888',
        'email' => 'match@example.com',
    ]);

    $linked = app(LinkGuestOrders::class)->handle($customer);

    expect($linked)->toBe(1)
        ->and($order->fresh()->customer_id)->toBe($customer->id);
});

it('matches the email case-insensitively', function () {
    $order = guestOrder(['contact_mobile' => '01099998888', 'contact_email' => 'Match@Example.com']);

    $customer = Customer::factory()->create([
        'mobile' => '01099998888',
        'email' => 'match@example.com',
    ]);

    expect(app(LinkGuestOrders::class)->handle($customer))->toBe(1)
        ->and($order->fresh()->customer_id)->toBe($customer->id);
});

it('does NOT link when only the mobile matches', function () {
    $order = guestOrder(['contact_mobile' => '01099998888', 'contact_email' => 'someone-else@example.com']);

    $customer = Customer::factory()->create([
        'mobile' => '01099998888',
        'email' => 'match@example.com',
    ]);

    expect(app(LinkGuestOrders::class)->handle($customer))->toBe(0)
        ->and($order->fresh()->customer_id)->toBeNull();
});

it('does NOT link when only the email matches', function () {
    $order = guestOrder(['contact_mobile' => '01000000000', 'contact_email' => 'match@example.com']);

    $customer = Customer::factory()->create([
        'mobile' => '01099998888',
        'email' => 'match@example.com',
    ]);

    expect(app(LinkGuestOrders::class)->handle($customer))->toBe(0)
        ->and($order->fresh()->customer_id)->toBeNull();
});

it('never steals an order already owned by another account', function () {
    $other = Customer::factory()->create();
    $order = guestOrder([
        'customer_id' => $other->id,
        'contact_mobile' => '01099998888',
        'contact_email' => 'match@example.com',
    ]);

    $customer = Customer::factory()->create([
        'mobile' => '01099998888',
        'email' => 'match@example.com',
    ]);

    expect(app(LinkGuestOrders::class)->handle($customer))->toBe(0)
        ->and($order->fresh()->customer_id)->toBe($other->id);
});

it('links matching guest orders when a customer registers', function () {
    $order = guestOrder(['contact_mobile' => '01055556666', 'contact_email' => 'newbie@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'New Buyer')
        ->set('mobile', '01055556666')
        ->set('email', 'newbie@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->set('terms', true)
        ->call('register')
        ->assertHasNoErrors();

    $customer = Customer::where('email', 'newbie@example.com')->first();

    expect($order->fresh()->customer_id)->toBe($customer->id);
});

/* ---------- merging the cart ---------- */

it('folds the guest cart into the customer cart on login, summing quantities', function () {
    $variantA = linkVariant('A');
    $variantB = linkVariant('B');

    // Guest cart in the current session.
    $guest = app(CartManager::class);
    $guest->add($variantA->id, 2);
    $guest->add($variantB->id, 1);

    // A customer who already has a cart holding variant A.
    $customer = Customer::factory()->create();
    $customer->carts()->create()->items()->create([
        'product_variant_id' => $variantA->id,
        'qty' => 3,
    ]);

    $this->actingAs($customer, 'customer');
    app(CartManager::class)->mergeGuestCartOnLogin();

    $customerCart = $customer->carts()->first();

    expect($customerCart->items()->where('product_variant_id', $variantA->id)->value('qty'))->toBe(5)
        ->and($customerCart->items()->where('product_variant_id', $variantB->id)->value('qty'))->toBe(1)
        ->and(Cart::whereNull('customer_id')->count())->toBe(0);
});

it('caps a merged line quantity at 99', function () {
    $variant = linkVariant('C');

    $guest = app(CartManager::class);
    $guest->add($variant->id, 60);

    $customer = Customer::factory()->create();
    $customer->carts()->create()->items()->create([
        'product_variant_id' => $variant->id,
        'qty' => 50,
    ]);

    $this->actingAs($customer, 'customer');
    app(CartManager::class)->mergeGuestCartOnLogin();

    expect($customer->carts()->first()->items()->where('product_variant_id', $variant->id)->value('qty'))->toBe(99);
});

it('merges the guest cart through the login component', function () {
    $variant = linkVariant('D');

    $guest = app(CartManager::class);
    $guest->add($variant->id, 2);

    $customer = Customer::factory()->create();

    Livewire::test(Login::class)
        ->set('identifier', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    expect($customer->carts()->first()?->items()->where('product_variant_id', $variant->id)->value('qty'))->toBe(2);
});
