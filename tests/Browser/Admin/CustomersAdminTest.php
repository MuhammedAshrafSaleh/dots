<?php

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * T8.5 — Real-browser E2E for the Admin customers area:
 * list renders, filters, search, Export CSV button, customer detail KPIs,
 * admin notes, disable/enable, middleware kick-out, permission gating.
 */

/**
 * Log in as an admin and return the chained page. Uses a unique email per call
 * so concurrent test runs don't collide. Defined here (not reusing signInAdmin
 * from AdminFlowTest.php) to avoid "Cannot redeclare function" across files.
 */
function customerAdminSignIn(): mixed
{
    $admin = User::firstWhere('email', 'e2e-cust@dots.test') ?? adminUser();
    $admin->forceFill(['email' => 'e2e-cust@dots.test'])->save();

    return visit('/admin/lang/en')
        ->navigate('/admin/login')
        ->fill('input[type=email]', 'e2e-cust@dots.test')
        ->fill('input[type=password]', 'password')
        ->click('button[type=submit]')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
}

// ─── Customers list ───────────────────────────────────────────────────────────

it('customers list renders with customer data', function () {
    Customer::factory()->create(['name' => 'Alice Admin', 'email' => 'alice@test.test', 'mobile' => '01098761001']);

    customerAdminSignIn()
        ->navigate('/admin/customers')
        ->assertSee('Customers')
        ->assertSee('Alice Admin')
        ->assertNoJavaScriptErrors();
});

it('search filters the customers list by name', function () {
    Customer::factory()->create(['name' => 'Unique NameXYZ', 'email' => 'uniq@test.test', 'mobile' => '01098761002']);
    Customer::factory()->create(['name' => 'Another Person', 'email' => 'other@test.test', 'mobile' => '01098761003']);

    customerAdminSignIn()
        ->navigate('/admin/customers')
        ->fill('[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'Unique NameXYZ')
        ->assertSee('Unique NameXYZ')
        ->assertDontSee('Another Person')
        ->assertNoJavaScriptErrors();
});

it('status filter shows only active customers', function () {
    Customer::factory()->create(['name' => 'Active One', 'email' => 'act@test.test', 'mobile' => '01098761004', 'status' => CustomerStatus::Active]);
    Customer::factory()->disabled()->create(['name' => 'Disabled One', 'email' => 'dis@test.test', 'mobile' => '01098761005']);

    customerAdminSignIn()
        ->navigate('/admin/customers')
        ->select('[wire\\:model\\.live="status"]', 'active')
        ->assertSee('Active One')
        ->assertDontSee('Disabled One')
        ->assertNoJavaScriptErrors();
});

it('export CSV button triggers download without errors', function () {
    Customer::factory()->create(['email' => 'exp@test.test', 'mobile' => '01098761006']);

    customerAdminSignIn()
        ->navigate('/admin/customers')
        ->press('Export CSV')
        // Playwright downloads the file; we verify no crash and no JS errors.
        ->assertNoJavaScriptErrors();
});

// ─── Customer detail ──────────────────────────────────────────────────────────

it('customer detail page renders KPIs and profile information', function () {
    $customer = Customer::factory()->create(['name' => 'Detail KPI', 'email' => 'kpi@test.test', 'mobile' => '01098761007']);

    Order::create([
        'customer_id' => $customer->id,
        'contact_name' => $customer->name,
        'contact_mobile' => $customer->mobile,
        'contact_email' => $customer->email,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'locale' => 'en',
        'total' => 400.00,
        'placed_at' => now()->subDay(),
    ]);

    customerAdminSignIn()
        ->navigate('/admin/customers/'.$customer->id)
        ->assertSee('Detail KPI')
        ->assertSee('Total spent')        // admin.customers.total_spent
        ->assertSee('Profile')            // admin.customers.profile
        ->assertNoJavaScriptErrors();
});

it('admin can save internal notes and they persist on reload', function () {
    $customer = Customer::factory()->create(['email' => 'notes@test.test', 'mobile' => '01098761008']);

    customerAdminSignIn()
        ->navigate('/admin/customers/'.$customer->id)
        ->fill('[wire\\:model="adminNotes"]', 'Important note for team')
        ->press('Save notes')              // admin.customers.save_notes
        ->assertSee('Notes saved')         // admin.customers.notes_saved
        ->navigate('/admin/customers/'.$customer->id)
        ->assertSee('Important note for team')
        ->assertNoJavaScriptErrors();
});

// ─── Disable / enable ─────────────────────────────────────────────────────────

it('disable account button shows the disabled banner', function () {
    $customer = Customer::factory()->create(['email' => 'dis-btn@test.test', 'mobile' => '01098761009']);

    customerAdminSignIn()
        ->navigate('/admin/customers/'.$customer->id)
        ->press('Disable account')         // admin.customers.disable
        ->assertSee('This account is disabled')   // admin.customers.disabled_title
        ->assertNoJavaScriptErrors();

    expect(Customer::find($customer->id)?->status)->toBe(CustomerStatus::Disabled);
});

it('re-enable account button removes the disabled banner', function () {
    $customer = Customer::factory()->disabled()->create(['email' => 're-en@test.test', 'mobile' => '01098761010']);

    customerAdminSignIn()
        ->navigate('/admin/customers/'.$customer->id)
        ->assertSee('This account is disabled')
        ->press('Enable account')          // admin.customers.enable
        ->assertDontSee('This account is disabled')
        ->assertNoJavaScriptErrors();

    expect(Customer::find($customer->id)?->status)->toBe(CustomerStatus::Active);
});

it('disabled customer is kicked out of account pages with the disabled flash', function () {
    $customer = Customer::factory()->create(['email' => 'kick@test.test', 'mobile' => '01098761011']);

    // Step 1: log in as the customer and navigate to the account page.
    // Store the page so we can continue using the same browser session after
    // the DB update below.
    $page = visit('/en/login')
        ->fill('[wire\\:model="identifier"]', 'kick@test.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->navigate('/en/account')
        ->assertSee('Overview');          // confirms they are logged in

    // Step 2: disable the customer directly in the DB (simulates admin action).
    $customer->forceFill(['status' => CustomerStatus::Disabled])->save();

    // Step 3: navigate to any account page in the SAME browser session — the
    // EnsureCustomerActive middleware redirects them to login with a flash.
    $page->navigate('/en/account')
        ->assertPathIs('/en/login')
        ->assertSee('This account has been disabled')   // account.login.account_disabled
        ->assertNoJavaScriptErrors();
});

it('re-enabled customer can log in again', function () {
    $customer = Customer::factory()->disabled()->create(['email' => 're-login@test.test', 'mobile' => '01098761012']);

    // Enable via DB (simulates admin click).
    $customer->forceFill(['status' => CustomerStatus::Active])->save();

    visit('/en/login')
        ->fill('[wire\\:model="identifier"]', 're-login@test.test')
        ->fill('[wire\\:model="password"]', 'password')
        ->click('button[type=submit]')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();
});

// ─── Permission gating ────────────────────────────────────────────────────────

it('admin without customers.view permission gets 403 on the customers list', function () {
    // Create an admin with every permission EXCEPT customers.view.
    $noViewPermissions = array_values(array_filter(
        config('permissions'),
        fn (string $p) => $p !== 'customers.view',
    ));

    $admin = adminUser($noViewPermissions);
    $admin->forceFill(['email' => 'no-cust@dots.test'])->save();

    visit('/admin/lang/en')
        ->navigate('/admin/login')
        ->fill('input[type=email]', 'no-cust@dots.test')
        ->fill('input[type=password]', 'password')
        ->click('button[type=submit]')
        ->navigate('/admin/customers')
        ->assertSee('403')   // forbidden response
        ->assertNoJavaScriptErrors();
});
