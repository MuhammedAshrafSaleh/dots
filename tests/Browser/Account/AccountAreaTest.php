<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * T8.3 — Real-browser E2E for the signed-in account area:
 * overview, orders list + empty state, order detail, stranger-order 404,
 * wishlist remove/add-all, addresses CRUD, profile edit, change password.
 *
 * Each test builds its own data. All navigation in a single chain so the
 * browser session cookie is kept throughout.
 */

/** Log in as customer and return the chained page so navigation can continue. */
function signInCustomer(Customer $customer, string $locale = 'en'): mixed
{
    return visit("/{$locale}/login")
        ->fill('[wire\\:model="identifier"]', $customer->email)
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]');
}

/** Minimal product + variant for wishlist and reorder tests. */
function accountTestProduct(string $suffix): ProductVariant
{
    $brand = Brand::create(['name' => 'AccBrand'.$suffix, 'slug' => 'acc-brand'.$suffix]);
    $root = Category::create([
        'level' => 1, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'ف'.$suffix, 'en' => 'R'.$suffix],
        'slug' => ['ar' => 'ف'.$suffix, 'en' => 'accr'.$suffix],
    ]);
    $grp = Category::create([
        'level' => 2, 'parent_id' => $root->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'م'.$suffix, 'en' => 'G'.$suffix],
        'slug' => ['ar' => 'م'.$suffix, 'en' => 'accg'.$suffix],
    ]);
    $leaf = Category::create([
        'level' => 3, 'parent_id' => $grp->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'و'.$suffix, 'en' => 'L'.$suffix],
        'slug' => ['ar' => 'و'.$suffix, 'en' => 'accl'.$suffix],
    ]);

    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $leaf->id,
        'name' => ['ar' => 'منتج حساب'.$suffix, 'en' => 'AccProd'.$suffix],
        'slug' => ['ar' => 'منتج-حساب'.$suffix, 'en' => 'acc-prod'.$suffix],
        'sku' => 'ACC-SKU'.$suffix,
        'status' => 'active',
        'published_at' => now()->subDay(),
    ]);

    return $product->variants()->create([
        'sku' => 'ACC-VAR'.$suffix,
        'price' => 300.00,
        'is_default' => true,
        'is_active' => true,
        'stock_qty' => 50,
    ]);
}

// ─── Overview ────────────────────────────────────────────────────────────────

it('account overview renders after login', function () {
    $customer = Customer::factory()->create(['email' => 'acc-ov@dots.test']);

    signInCustomer($customer)
        ->navigate('/en/account')
        ->assertSee('Overview')
        ->assertSee($customer->name)
        ->assertNoJavaScriptErrors();
});

// ─── Orders list ─────────────────────────────────────────────────────────────

it('orders list shows empty state when the customer has no orders', function () {
    $customer = Customer::factory()->create(['email' => 'acc-empty-ord@dots.test']);

    signInCustomer($customer)
        ->navigate('/en/account/orders')
        ->assertSee('No orders yet')
        ->assertNoJavaScriptErrors();
});

it('orders list shows an existing order', function () {
    $customer = Customer::factory()->create(['email' => 'acc-has-ord@dots.test']);

    Order::create([
        'customer_id' => $customer->id,
        'contact_name' => 'Has Order',
        'contact_mobile' => $customer->mobile,
        'contact_email' => $customer->email,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'locale' => 'en',
        'total' => 500.00,
        'placed_at' => now()->subDay(),
    ]);

    signInCustomer($customer)
        ->navigate('/en/account/orders')
        ->assertDontSee('No orders yet')
        ->assertSee('DT-')             // order number prefix
        ->assertNoJavaScriptErrors();
});

// ─── Order detail ─────────────────────────────────────────────────────────────

it('order detail page shows the customer\'s own order', function () {
    $customer = Customer::factory()->create(['email' => 'acc-od@dots.test']);

    $order = Order::create([
        'customer_id' => $customer->id,
        'contact_name' => 'Detail Test',
        'contact_mobile' => $customer->mobile,
        'contact_email' => $customer->email,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'locale' => 'en',
        'total' => 750.00,
        'placed_at' => now()->subDay(),
    ]);
    $order->refresh(); // refresh to get auto-generated number

    signInCustomer($customer)
        ->navigate('/en/account/orders/'.$order->number)
        ->assertSee($order->number)
        ->assertSee('Payment summary')   // account.order.payment_summary
        ->assertNoJavaScriptErrors();
});

it('stranger cannot view another customer\'s order detail — gets 404', function () {
    $owner = Customer::factory()->create(['email' => 'acc-owner@dots.test']);
    $stranger = Customer::factory()->create(['email' => 'acc-stranger@dots.test']);

    $order = Order::create([
        'customer_id' => $owner->id,
        'contact_name' => 'Owner',
        'contact_mobile' => $owner->mobile,
        'contact_email' => $owner->email,
        'status' => 'new',
        'payment_status' => 'pending',
        'locale' => 'en',
    ]);
    $order->refresh();

    signInCustomer($stranger)
        ->navigate('/en/account/orders/'.$order->number)
        ->assertSee('404')            // 404 page or "Page Not Found"
        ->assertNoJavaScriptErrors();
});

// ─── Wishlist ─────────────────────────────────────────────────────────────────

it('wishlist page shows empty state when no products are saved', function () {
    $customer = Customer::factory()->create(['email' => 'acc-wl-empty@dots.test']);

    signInCustomer($customer)
        ->navigate('/en/account/wishlist')
        ->assertSee('Your wishlist is empty')
        ->assertNoJavaScriptErrors();
});

it('wishlist remove button removes the product from the list', function () {
    $variant = accountTestProduct('-wl');
    $customer = Customer::factory()->create(['email' => 'acc-wl-rm@dots.test']);
    $customer->wishlist()->attach($variant->product_id, ['created_at' => now()]);

    signInCustomer($customer)
        ->navigate('/en/account/wishlist')
        ->assertSee('AccProd-wl')          // the product name
        ->press('Remove')                  // account.wishlist.remove
        ->assertSee('Your wishlist is empty')
        ->assertNoJavaScriptErrors();
});

// ─── Addresses ────────────────────────────────────────────────────────────────

it('adds a new address and sees the saved confirmation', function () {
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'is_active' => true]);
    $customer = Customer::factory()->create(['email' => 'acc-addr@dots.test']);

    signInCustomer($customer)
        ->navigate('/en/account/addresses')
        ->assertSee('No saved addresses')
        ->press('Add a new address')        // account.addresses.add_new
        ->fill('[wire\\:model="recipient_name"]', 'Test Recipient')
        ->fill('[wire\\:model="mobile"]', '01012340002')
        ->select('[wire\\:model="governorate_id"]', (string) $gov->id)
        ->fill('[wire\\:model="city"]', 'Nasr City')
        ->fill('[wire\\:model="street"]', '10 Example St')
        ->press('Save address')             // account.addresses.save
        ->assertSee('Address saved')        // account.addresses.saved
        ->assertNoJavaScriptErrors();

    expect($customer->addresses()->count())->toBe(1);
});

// ─── Profile ─────────────────────────────────────────────────────────────────

it('profile page updates successfully', function () {
    $customer = Customer::factory()->create(['email' => 'acc-profile@dots.test', 'name' => 'Old Name']);

    signInCustomer($customer)
        ->navigate('/en/account/profile')
        ->assertSee('Profile')
        ->clear('[wire\\:model="name"]')
        ->fill('[wire\\:model="name"]', 'New Name Updated')
        ->press('Save changes')            // account.profile.save
        ->assertSee('Profile updated')     // account.profile.details_saved
        ->assertNoJavaScriptErrors();

    expect(Customer::find($customer->id)?->name)->toBe('New Name Updated');
});

it('wrong current password shows an error on the change-password form', function () {
    $customer = Customer::factory()->create(['email' => 'acc-pw@dots.test']);

    signInCustomer($customer)
        ->navigate('/en/account/profile')
        ->fill('[wire\\:model="current_password"]', 'wrongpassword')
        ->fill('[wire\\:model="password"]', 'newpass12')
        ->fill('[wire\\:model="password_confirmation"]', 'newpass12')
        ->press('Update password')                          // account.profile.update_password
        ->assertSee('Your current password is incorrect')  // account.profile.current_wrong
        ->assertNoJavaScriptErrors();
});
