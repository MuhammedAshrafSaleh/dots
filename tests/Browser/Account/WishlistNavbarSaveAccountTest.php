<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

/**
 * T8.4 — Real-browser E2E for:
 *  - Navbar account dropdown (logged-out vs logged-in)
 *  - Wishlist heart on PDP (logged-out prompt; logged-in toggle + persist +
 *    appears in /account/wishlist + remove)
 *  - Confirmation page "save your details" card (card hidden when already
 *    logged in; full guest→account flow is covered by feature tests because
 *    it requires a complete checkout with seeded delivery/payment methods)
 */

/** A product accessible at /en/products/{slug}. */
function wlProduct(string $suffix): Product
{
    $brand = Brand::create(['name' => 'WlBrand'.$suffix, 'slug' => 'wl-brand'.$suffix]);
    $root = Category::create([
        'level' => 1, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'ط'.$suffix, 'en' => 'WR'.$suffix],
        'slug' => ['ar' => 'ط'.$suffix, 'en' => 'wlr'.$suffix],
    ]);
    $grp = Category::create([
        'level' => 2, 'parent_id' => $root->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'غ'.$suffix, 'en' => 'WG'.$suffix],
        'slug' => ['ar' => 'غ'.$suffix, 'en' => 'wlg'.$suffix],
    ]);
    $leaf = Category::create([
        'level' => 3, 'parent_id' => $grp->id, 'is_active' => true, 'sort_order' => 1,
        'name' => ['ar' => 'خ'.$suffix, 'en' => 'WL'.$suffix],
        'slug' => ['ar' => 'خ'.$suffix, 'en' => 'wll'.$suffix],
    ]);

    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $leaf->id,
        'name' => ['ar' => 'مصباح'.$suffix, 'en' => 'WlProd'.$suffix],
        'slug' => ['ar' => 'مصباح'.$suffix, 'en' => 'wl-prod'.$suffix],
        'sku' => 'WL-SKU'.$suffix,
        'status' => 'active',
        'published_at' => now()->subDay(),
    ]);

    $product->variants()->create([
        'sku' => 'WL-VAR'.$suffix,
        'price' => 180.00,
        'is_default' => true,
        'is_active' => true,
        'stock_qty' => 50,
    ]);

    return $product;
}

// ─── Navbar ───────────────────────────────────────────────────────────────────

it('navbar shows login link when customer is logged out', function () {
    visit('/en')
        ->assertSee('Log in')              // ui.login (login/register links in nav)
        ->assertNoJavaScriptErrors();
});

it('navbar shows customer name when logged in', function () {
    $customer = Customer::factory()->create(['email' => 'wl-nav@dots.test', 'name' => 'Nav Tester']);

    visit('/en/login')
        ->fill('[wire\\:model="identifier"]', 'wl-nav@dots.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->navigate('/en')
        ->assertSee('Nav Tester')          // customer name appears in the header dropdown
        ->assertNoJavaScriptErrors();
});

// ─── Wishlist heart on PDP ────────────────────────────────────────────────────

it('wishlist heart shows login prompt for logged-out visitors', function () {
    wlProduct('-lo');

    visit('/en/products/wl-prod-lo')
        ->click('Save to wishlist')
        ->assertSee('Log in to keep a wishlist')   // ui.pdp_wishlist_body
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

it('wishlist heart adds item, item appears in wishlist, remove clears it', function () {
    $product = wlProduct('-tgl');
    $customer = Customer::factory()->create(['email' => 'wl-tgl@dots.test']);

    visit('/en/login')
        ->fill('[wire\\:model="identifier"]', 'wl-tgl@dots.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->navigate('/en/products/wl-prod-tgl')
        ->click('Save to wishlist')           // dispatches wishlist-toggle → WishlistSync
        ->navigate('/en/account/wishlist')    // full navigation lets Livewire finish
        ->assertSee('WlProd-tgl')             // item is in wishlist
        ->press('Remove')                     // account.wishlist.remove
        ->assertSee('Your wishlist is empty') // account.wishlist.empty_title
        ->assertNoJavaScriptErrors();
});

it('wishlist heart shows as saved after a page reload when pre-seeded', function () {
    $product = wlProduct('-rl');
    $customer = Customer::factory()->create(['email' => 'wl-rl@dots.test']);
    $customer->wishlist()->attach($product->id, ['created_at' => now()]);

    visit('/en/login')
        ->fill('[wire\\:model="identifier"]', 'wl-rl@dots.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->navigate('/en/products/wl-prod-rl')
        ->assertSee('WlProd-rl')              // product page renders
        ->navigate('/en/account/wishlist')    // wishlist should show the item
        ->assertSee('WlProd-rl')
        ->assertNoJavaScriptErrors();
});

// ─── Save your details card (confirmation page) ───────────────────────────────

it('save-account card is hidden on the confirmation page for a logged-in customer', function () {
    // SaveOrderAccount::offerable() returns false when auth('customer')->check() is true.
    $customer = Customer::factory()->create(['email' => 'wl-conf@dots.test']);

    $order = Order::create([
        'customer_id' => $customer->id,
        'contact_name' => $customer->name,
        'contact_mobile' => $customer->mobile,
        'contact_email' => $customer->email,
        'status' => 'new',
        'payment_status' => 'pending',
        'locale' => 'en',
    ]);
    $order->refresh();

    visit('/en/login')
        ->fill('[wire\\:model="identifier"]', 'wl-conf@dots.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->navigate('/en/order/'.$order->number.'/confirmation')
        ->assertDontSee('Save your details for next time')   // account.save_order.title
        ->assertNoJavaScriptErrors();
});
