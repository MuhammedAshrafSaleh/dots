<?php

use App\Enums\ProductStatus;
use App\Livewire\Account\SaveOrderAccount;
use App\Livewire\Shop\WishlistSync;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

function wiringProduct(string $sku): Product
{
    $brand = Brand::create(['name' => 'B'.$sku, 'slug' => 'b-'.strtolower($sku)]);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'C'],
        'slug' => ['ar' => 'ق-'.$sku, 'en' => 'c-'.strtolower($sku)],
    ]);

    return Product::create([
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
}

function wiringGuestOrder(array $overrides = []): Order
{
    return Order::create(array_merge([
        'contact_name' => 'Guest Buyer',
        'contact_mobile' => '01012345678',
        'contact_email' => 'buyer@example.com',
    ], $overrides));
}

/* ---------- navbar account dropdown ---------- */

it('shows the account menu links to a signed-in customer in the header', function () {
    $customer = Customer::factory()->create(['name' => 'Mona Adel', 'email' => 'mona@example.com']);

    $html = $this->actingAs($customer, 'customer')->get('/en')->assertOk()->content();

    expect($html)
        ->toContain('/en/account/orders')
        ->toContain('/en/account/wishlist')
        ->toContain('/en/account/addresses')
        ->toContain('/en/account/profile')
        ->toContain('Mona');
});

it('shows a login link to a guest in the header', function () {
    $html = $this->get('/en')->assertOk()->content();

    expect($html)
        ->toContain('/en/login')
        ->not->toContain('/en/account/orders');
});

/* ---------- wishlist hearts ---------- */

it('adds then removes a product from the wishlist when a customer toggles the heart', function () {
    $customer = Customer::factory()->create();
    $product = wiringProduct('WISH');

    $this->actingAs($customer, 'customer');

    Livewire::test(WishlistSync::class)->call('toggle', $product->id);
    expect($customer->wishlist()->whereKey($product->id)->exists())->toBeTrue();

    Livewire::test(WishlistSync::class)->call('toggle', $product->id);
    expect($customer->wishlist()->whereKey($product->id)->exists())->toBeFalse();
});

it('ignores a wishlist toggle from a guest', function () {
    $product = wiringProduct('GUESTWISH');

    Livewire::test(WishlistSync::class)->call('toggle', $product->id);

    expect(DB::table('wishlists')->count())->toBe(0);
});

/* ---------- confirmation: save your details ---------- */

it('creates an account from a guest order and links that order', function () {
    $order = wiringGuestOrder(['contact_mobile' => '01055556666', 'contact_email' => 'saver@example.com', 'contact_name' => 'Sara Nabil']);

    Livewire::test(SaveOrderAccount::class, ['order' => $order])
        ->assertStatus(200)
        ->set('password', 'secret123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $customer = Customer::where('email', 'saver@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('Sara Nabil')
        ->and($customer->mobile)->toBe('01055556666')
        ->and($order->fresh()->customer_id)->toBe($customer->id)
        ->and(auth('customer')->id())->toBe($customer->id);
});

it('rejects a weak password on the save-your-details card', function () {
    $order = wiringGuestOrder(['contact_email' => 'weak@example.com']);

    Livewire::test(SaveOrderAccount::class, ['order' => $order])
        ->set('password', 'short')
        ->call('save')
        ->assertHasErrors('password');

    expect(Customer::where('email', 'weak@example.com')->exists())->toBeFalse();
});

it('does not offer the card when an account already exists for the order email', function () {
    Customer::factory()->create(['email' => 'taken@example.com', 'mobile' => '01111111111']);
    $order = wiringGuestOrder(['contact_email' => 'taken@example.com', 'contact_mobile' => '01222222222']);

    expect(Livewire::test(SaveOrderAccount::class, ['order' => $order])->instance()->offerable())->toBeFalse();
});

it('does not offer the card to an already signed-in customer', function () {
    $customer = Customer::factory()->create();
    $order = wiringGuestOrder(['contact_email' => 'other@example.com']);

    $this->actingAs($customer, 'customer');

    expect(Livewire::test(SaveOrderAccount::class, ['order' => $order])->instance()->offerable())->toBeFalse();
});

it('does not offer the card when the order has no email', function () {
    $order = wiringGuestOrder(['contact_email' => null]);

    expect(Livewire::test(SaveOrderAccount::class, ['order' => $order])->instance()->offerable())->toBeFalse();
});
