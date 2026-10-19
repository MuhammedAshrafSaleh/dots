<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Livewire\Account\Addresses;
use App\Livewire\Account\OrderDetail;
use App\Livewire\Account\Orders;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Wishlist;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function accountVariant(string $sku, float $price = 100): ProductVariant
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
        'stock_qty' => 10,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

function accountOrder(Customer $customer, array $overrides = []): Order
{
    $order = Order::create(array_merge([
        'customer_id' => $customer->id,
        'contact_name' => $customer->name,
        'contact_mobile' => $customer->mobile,
        'contact_email' => $customer->email,
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

    return $order;
}

/* ---------- access control ---------- */

it('redirects a guest from the account area to login', function () {
    $this->get(route('account.overview', 'ar'))
        ->assertRedirect(route('login', 'ar'));
});

it('shows the overview to a signed-in customer', function () {
    $customer = Customer::factory()->create(['name' => 'Mona Adel']);

    $this->actingAs($customer, 'customer')
        ->get(route('account.overview', 'ar'))
        ->assertOk()
        ->assertSee('Mona');
});

/* ---------- orders ---------- */

it('lists only the customer’s own orders', function () {
    $customer = Customer::factory()->create();
    $order = accountOrder($customer);

    Livewire::actingAs($customer, 'customer')
        ->test(Orders::class)
        ->assertSee($order->number);
});

it('shows the empty state when the customer has no orders', function () {
    $customer = Customer::factory()->create();

    Livewire::actingAs($customer, 'customer')
        ->test(Orders::class)
        ->assertSee(__('account.orders.empty_title'));
});

it('lets the owner open their order detail', function () {
    $customer = Customer::factory()->create();
    $order = accountOrder($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('account.order', ['ar', $order->number]))
        ->assertOk()
        ->assertSee($order->number);
});

it('404s when a customer opens another customer’s order', function () {
    $owner = Customer::factory()->create();
    $stranger = Customer::factory()->create();
    $order = accountOrder($owner);

    $this->actingAs($stranger, 'customer')
        ->get(route('account.order', ['ar', $order->number]))
        ->assertNotFound();
});

it('404s when a customer opens a guest order', function () {
    $customer = Customer::factory()->create();
    $guestOrder = accountOrder($customer, ['customer_id' => null]);

    $this->actingAs($customer, 'customer')
        ->get(route('account.order', ['ar', $guestOrder->number]))
        ->assertNotFound();
});

it('reorders the items from a past order into the cart', function () {
    $customer = Customer::factory()->create();
    $variant = accountVariant('RE', 250);
    $order = accountOrder($customer);
    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'منتج', 'en' => 'Item'],
        'sku' => $variant->sku,
        'unit_price' => 250,
        'qty' => 2,
        'line_total' => 500,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrderDetail::class, ['order' => $order])
        ->call('reorder');

    expect(app(CartManager::class)->itemCount())->toBe(2);
});

/* ---------- wishlist ---------- */

it('removes a product from the wishlist', function () {
    $customer = Customer::factory()->create();
    $variant = accountVariant('WL', 300);
    $customer->wishlist()->attach($variant->product_id, ['created_at' => now()]);

    Livewire::actingAs($customer, 'customer')
        ->test(Wishlist::class)
        ->assertSee('منتج WL')
        ->call('remove', $variant->product_id);

    expect($customer->wishlist()->count())->toBe(0);
});

it('adds all buyable wishlist products to the cart', function () {
    $customer = Customer::factory()->create();
    $a = accountVariant('WA', 100);
    $b = accountVariant('WB', 200);
    $customer->wishlist()->attach([$a->product_id, $b->product_id], ['created_at' => now()]);

    Livewire::actingAs($customer, 'customer')
        ->test(Wishlist::class)
        ->call('addAll');

    expect(app(CartManager::class)->itemCount())->toBe(2);
});

/* ---------- addresses ---------- */

it('creates the first address as the default', function () {
    $customer = Customer::factory()->create();
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'is_active' => true]);

    Livewire::actingAs($customer, 'customer')
        ->test(Addresses::class)
        ->call('create')
        ->set('recipient_name', 'Mona Adel')
        ->set('mobile', '01012345678')
        ->set('governorate_id', $gov->id)
        ->set('city', 'Maadi')
        ->set('street', '14 El-Nahda St')
        ->set('floor_apartment', '3 / 6')
        ->call('save')
        ->assertHasNoErrors();

    $address = $customer->addresses()->first();
    expect($address)->not->toBeNull()
        ->and($address->is_default)->toBeTrue();
});

it('moves the default when a new default address is saved', function () {
    $customer = Customer::factory()->create();
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'is_active' => true]);
    $first = $customer->addresses()->create([
        'label' => 'home', 'recipient_name' => 'A', 'mobile' => '01012345678',
        'governorate_id' => $gov->id, 'city' => 'C', 'street' => 'S', 'floor_apartment' => '1', 'is_default' => true,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(Addresses::class)
        ->call('create')
        ->set('recipient_name', 'B')
        ->set('mobile', '01112345678')
        ->set('governorate_id', $gov->id)
        ->set('city', 'C2')
        ->set('street', 'S2')
        ->set('floor_apartment', '2')
        ->set('is_default', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($customer->addresses()->where('is_default', true)->count())->toBe(1);
});

it('promotes another address to default when the default is deleted', function () {
    $customer = Customer::factory()->create();
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'is_active' => true]);
    $def = $customer->addresses()->create([
        'label' => 'home', 'recipient_name' => 'A', 'mobile' => '01012345678',
        'governorate_id' => $gov->id, 'city' => 'C', 'street' => 'S', 'floor_apartment' => '1', 'is_default' => true,
    ]);
    $other = $customer->addresses()->create([
        'label' => 'office', 'recipient_name' => 'B', 'mobile' => '01112345678',
        'governorate_id' => $gov->id, 'city' => 'C2', 'street' => 'S2', 'floor_apartment' => '2', 'is_default' => false,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(Addresses::class)
        ->call('confirmDelete', $def->id)
        ->call('delete');

    expect($customer->addresses()->count())->toBe(1)
        ->and($other->fresh()->is_default)->toBeTrue();
});

it('rejects an invalid mobile on an address', function () {
    $customer = Customer::factory()->create();
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'is_active' => true]);

    Livewire::actingAs($customer, 'customer')
        ->test(Addresses::class)
        ->call('create')
        ->set('recipient_name', 'Mona')
        ->set('mobile', '999')
        ->set('governorate_id', $gov->id)
        ->set('city', 'C')
        ->set('street', 'S')
        ->set('floor_apartment', '1')
        ->call('save')
        ->assertHasErrors('mobile');
});

/* ---------- profile ---------- */

it('updates personal details', function () {
    $customer = Customer::factory()->create();

    Livewire::actingAs($customer, 'customer')
        ->test(Profile::class)
        ->set('name', 'Mona Adel')
        ->set('mobile', '01099988877')
        ->set('email', 'mona.new@example.com')
        ->call('saveDetails')
        ->assertHasNoErrors();

    expect($customer->fresh()->email)->toBe('mona.new@example.com');
});

it('rejects an email already used by another customer', function () {
    $other = Customer::factory()->create(['email' => 'taken@example.com']);
    $customer = Customer::factory()->create();

    Livewire::actingAs($customer, 'customer')
        ->test(Profile::class)
        ->set('name', 'Mona Adel')
        ->set('email', 'taken@example.com')
        ->call('saveDetails')
        ->assertHasErrors('email');
});

it('refuses a password change with the wrong current password', function () {
    $customer = Customer::factory()->create();

    Livewire::actingAs($customer, 'customer')
        ->test(Profile::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'newpass123')
        ->set('password_confirmation', 'newpass123')
        ->call('updatePassword')
        ->assertHasErrors('current_password');
});

it('changes the password with the correct current password', function () {
    $customer = Customer::factory()->create();

    Livewire::actingAs($customer, 'customer')
        ->test(Profile::class)
        ->set('current_password', 'password')
        ->set('password', 'newpass123')
        ->set('password_confirmation', 'newpass123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('newpass123', $customer->fresh()->password))->toBeTrue();
});
