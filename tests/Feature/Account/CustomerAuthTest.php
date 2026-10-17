<?php

use App\Enums\CustomerStatus;
use App\Livewire\Account\Auth\ForgotPassword;
use App\Livewire\Account\Auth\Login;
use App\Livewire\Account\Auth\Register;
use App\Livewire\Account\Auth\ResetPassword;
use App\Models\Customer;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

/* ---------- screens render ---------- */

it('renders the login, register and forgot screens for guests', function () {
    $this->get(route('login', 'ar'))->assertOk()->assertSee(__('account.login.title'));
    $this->get(route('register', 'ar'))->assertOk()->assertSee(__('account.register.title'));
    $this->get(route('password.request', 'ar'))->assertOk()->assertSee(__('account.forgot.title'));
});

it('redirects an authenticated customer away from the login screen', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('login', 'ar'))
        ->assertStatus(302);
});

/* ---------- login ---------- */

it('logs in with the email and records the login time', function () {
    $customer = Customer::factory()->create();

    Livewire::test(Login::class)
        ->set('identifier', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('home', 'ar'));

    expect(auth('customer')->check())->toBeTrue()
        ->and($customer->fresh()->last_login_at)->not->toBeNull();
});

it('logs in with the mobile number, ignoring spaces and dashes', function () {
    $customer = Customer::factory()->create(['mobile' => '01012345678']);

    Livewire::test(Login::class)
        ->set('identifier', '0101 234-5678')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    expect(auth('customer')->check())->toBeTrue();
});

it('rejects an identifier that is neither a valid email nor an Egyptian mobile', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'not-valid')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth('customer')->check())->toBeFalse();
});

it('rejects a wrong password and shows remaining attempts', function () {
    $customer = Customer::factory()->create();

    Livewire::test(Login::class)
        ->set('identifier', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('identifier')
        ->assertSet('attemptsLeft', 4);

    expect(auth('customer')->check())->toBeFalse();
});

it('does not log in a disabled customer even with the right password', function () {
    $customer = Customer::factory()->disabled()->create();

    Livewire::test(Login::class)
        ->set('identifier', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth('customer')->check())->toBeFalse();
});

it('locks the account after five failed attempts', function () {
    $customer = Customer::factory()->create();

    foreach (range(1, 5) as $ignored) {
        Livewire::test(Login::class)
            ->set('identifier', $customer->email)
            ->set('password', 'nope')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('identifier', $customer->email)
        ->set('password', 'password') // correct now, but locked
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth('customer')->check())->toBeFalse();
});

it('returns to checkout after logging in from checkout', function () {
    $customer = Customer::factory()->create();

    Livewire::test(Login::class)
        ->set('next', 'checkout')
        ->set('identifier', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('checkout', 'ar'));
});

/* ---------- register ---------- */

it('creates an account, logs in and redirects home', function () {
    Livewire::test(Register::class)
        ->set('name', 'Mona Adel')
        ->set('mobile', '01012345678')
        ->set('email', 'mona@example.com')
        ->set('password', 'secret12')
        ->set('terms', true)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('home', 'ar'));

    $customer = Customer::where('email', 'mona@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->status)->toBe(CustomerStatus::Active)
        ->and(auth('customer')->id())->toBe($customer->id);
});

it('rejects registration that fails the PRD rules', function () {
    Livewire::test(Register::class)
        ->set('name', 'Mona')            // single name
        ->set('mobile', '0101234')       // not a full Egyptian mobile
        ->set('email', 'mona@invalid')   // invalid email
        ->set('password', 'short')       // < 8 chars, no digit
        ->set('terms', false)            // not accepted
        ->call('register')
        ->assertHasErrors(['name', 'mobile', 'email', 'password', 'terms']);

    expect(Customer::count())->toBe(0);
});

it('rejects a password without a number', function () {
    Livewire::test(Register::class)
        ->set('name', 'Mona Adel')
        ->set('mobile', '01012345678')
        ->set('email', 'mona@example.com')
        ->set('password', 'password')
        ->set('terms', true)
        ->call('register')
        ->assertHasErrors('password');
});

it('rejects a mobile or email already in use', function () {
    Customer::factory()->create(['mobile' => '01099998888', 'email' => 'taken@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'Mona Adel')
        ->set('mobile', '01099998888')
        ->set('email', 'taken@example.com')
        ->set('password', 'secret12')
        ->set('terms', true)
        ->call('register')
        ->assertHasErrors(['mobile', 'email']);
});

/* ---------- forgot password ---------- */

it('sends a reset link and starts the resend cooldown', function () {
    Notification::fake();
    $customer = Customer::factory()->create();

    Livewire::test(ForgotPassword::class)
        ->set('email', $customer->email)
        ->call('send')
        ->assertSet('sent', true)
        ->assertSet('cooldown', 60);

    Notification::assertSentTo($customer, CustomerResetPasswordNotification::class);
});

it('does not reveal whether an email is registered', function () {
    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody@example.com')
        ->call('send')
        ->assertSet('sent', true)
        ->assertHasNoErrors();

    Notification::assertNothingSent();
});

/* ---------- reset password ---------- */

it('resets the password and logs out other devices by rotating the remember token', function () {
    $customer = Customer::factory()->create();
    $customer->forceFill(['remember_token' => 'old-remember-token'])->save();
    $token = Password::broker('customers')->createToken($customer);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'new-pass1')
        ->set('password_confirmation', 'new-pass1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('done', true);

    $fresh = $customer->fresh();

    expect(Hash::check('new-pass1', $fresh->password))->toBeTrue()
        ->and($fresh->remember_token)->not->toBe('old-remember-token');
});

it('rejects a reset with an invalid token', function () {
    $customer = Customer::factory()->create();

    Livewire::test(ResetPassword::class, ['token' => 'bogus-token'])
        ->set('email', $customer->email)
        ->set('password', 'new-pass1')
        ->set('password_confirmation', 'new-pass1')
        ->call('save')
        ->assertHasErrors('email');

    expect(Hash::check('new-pass1', $customer->fresh()->password))->toBeFalse();
});

/* ---------- logout ---------- */

it('logs the customer out', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->post(route('logout', 'ar'))
        ->assertRedirect(route('home', 'ar'));

    expect(auth('customer')->check())->toBeFalse();
});
