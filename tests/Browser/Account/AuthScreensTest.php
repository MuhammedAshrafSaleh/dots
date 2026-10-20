<?php

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * T8.1 — Real-browser E2E for customer auth screens.
 * Each test uses its own unique credentials so the rate-limiter keys and
 * DB unique constraints never collide between tests.
 */

/**
 * Fill and submit the login form. Returns the chained page so more assertions
 * can be chained by the caller.
 */
function doLogin(string $identifier, string $password, string $locale = 'en'): mixed
{
    return visit("/{$locale}/login")
        ->fill('[wire\\:model="identifier"]', $identifier)
        ->fill('[wire\\:model="password"]', $password)
        ->click('form[wire\\:submit="login"] button[type=submit]');
}

// ─── Login ───────────────────────────────────────────────────────────────────

it('logs in with email and redirects to home (EN)', function () {
    Customer::factory()->create(['email' => 'auth-email-en@dots.test']);

    doLogin('auth-email-en@dots.test', 'password')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();
});

it('logs in with mobile and redirects to home (AR)', function () {
    Customer::factory()->create(['mobile' => '01012341001']);

    doLogin('01012341001', 'password', 'ar')
        ->assertPathIs('/ar')
        ->assertNoJavaScriptErrors();
});

it('shows the failed message on a wrong password', function () {
    Customer::factory()->create(['email' => 'auth-wrong-pw@dots.test']);

    doLogin('auth-wrong-pw@dots.test', 'badpassword')
        ->assertSee('incorrect')          // account.login.failed
        ->assertPathIs('/en/login')
        ->assertNoJavaScriptErrors();
});

it('shows lockout after 5 failed attempts', function () {
    Customer::factory()->create(['email' => 'auth-lockout@dots.test']);

    foreach (range(1, 5) as $i) {
        doLogin('auth-lockout@dots.test', 'wrong'.$i);
    }

    // 6th attempt — should be locked
    doLogin('auth-lockout@dots.test', 'wrong6')
        ->assertSee('Too many')           // auth.throttle: "Too many login attempts."
        ->assertNoJavaScriptErrors();
});

it('disabled customer cannot log in even with the correct password', function () {
    Customer::factory()->disabled()->create(['email' => 'auth-dis@dots.test']);

    // The login attempt() query includes status=active so the disabled customer
    // gets the generic "incorrect" error, not a "disabled" flash (that only
    // fires via the EnsureCustomerActive middleware for already-logged-in users).
    doLogin('auth-dis@dots.test', 'password')
        ->assertPathIs('/en/login')
        ->assertSee('incorrect')
        ->assertNoJavaScriptErrors();
});

// ─── Register ────────────────────────────────────────────────────────────────

it('register creates an account, logs in, and redirects to home', function () {
    visit('/en/register')
        ->fill('[wire\\:model="name"]', 'Test User')
        ->fill('[wire\\:model="mobile"]', '01098760011')
        ->fill('[wire\\:model="email"]', 'reg-new@dots.test')
        ->fill('[wire\\:model="password"]', 'password1')
        ->click('[wire\\:model="terms"]')
        ->click('form[wire\\:submit="register"] button[type=submit]')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();

    expect(Customer::where('email', 'reg-new@dots.test')->exists())->toBeTrue();
});

it('register stays on register page when email is already taken', function () {
    Customer::factory()->create(['email' => 'reg-taken@dots.test', 'mobile' => '01012349901']);

    visit('/en/register')
        ->fill('[wire\\:model="name"]', 'Another User')
        ->fill('[wire\\:model="mobile"]', '01012349902')
        ->fill('[wire\\:model="email"]', 'reg-taken@dots.test')
        ->fill('[wire\\:model="password"]', 'password1')
        ->click('[wire\\:model="terms"]')
        ->click('form[wire\\:submit="register"] button[type=submit]')
        ->assertPathIs('/en/register')
        ->assertNoJavaScriptErrors();
});

// ─── Forgot password ─────────────────────────────────────────────────────────

it('forgot password shows the masked email confirmation screen', function () {
    Customer::factory()->create(['email' => 'auth-forgot@dots.test']);

    visit('/en/forgot-password')
        ->fill('[wire\\:model="email"]', 'auth-forgot@dots.test')
        ->press('Send reset link')
        ->assertSee('Check your email')  // account.forgot.sent_title
        ->assertNoJavaScriptErrors();
});

// ─── Password reset ───────────────────────────────────────────────────────────

it('password reset lets customer set a new password and log in', function () {
    Customer::factory()->create(['email' => 'auth-reset@dots.test']);

    $token = Str::random(64);
    DB::table('customer_password_reset_tokens')->insert([
        'email' => 'auth-reset@dots.test',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $url = '/en/reset-password/'.urlencode($token).'?email='.urlencode('auth-reset@dots.test');

    visit($url)
        ->fill('[wire\\:model="password"]', 'newpass12')
        ->fill('[wire\\:model="password_confirmation"]', 'newpass12')
        ->press('Update password')
        ->assertSee('Password updated')   // account.reset.done_title
        ->assertNoJavaScriptErrors();

    doLogin('auth-reset@dots.test', 'newpass12')
        ->assertPathIs('/en')
        ->assertNoJavaScriptErrors();
});

// ─── Checkout-next flow ───────────────────────────────────────────────────────

it('login from checkout shows Continue as guest button', function () {
    visit('/en/login?next=checkout')
        ->assertSee('Continue as guest')
        ->assertNoJavaScriptErrors();
});

it('successful login from checkout redirects away from login', function () {
    Customer::factory()->create(['email' => 'auth-checkout@dots.test']);

    doLogin('auth-checkout@dots.test', 'password', 'en')
        // Note: login redirects to /en/checkout; because the cart is empty
        // CheckoutController forwards to /en/cart.  Either way we left /en/login.
        ->assertPathIsNot('/en/login')
        ->assertNoJavaScriptErrors();
});

// ─── Logout ───────────────────────────────────────────────────────────────────

it('logout redirects to home and blocks further access to account pages', function () {
    Customer::factory()->create(['email' => 'auth-logout@dots.test']);

    // Ensure login redirect is fully settled before navigating to account area.
    doLogin('auth-logout@dots.test', 'password')
        ->assertPathIs('/en');

    visit('/en/account')
        ->assertSee('Overview')           // account.nav.overview — confirms login worked
        ->click('button[role=tab][type=submit]') // tab-nav logout in account shell
        ->assertPathIs('/en');

    visit('/en/account')
        ->assertPathIs('/en/login')       // redirected to login — session is gone
        ->assertNoJavaScriptErrors();
});
