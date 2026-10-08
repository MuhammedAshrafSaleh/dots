<?php

use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(fn () => Cache::flush());

/** An active admin holding the given permissions via the Admin role. */
function makeAdmin(array $permissions = ['dashboard.view'], array $attrs = []): User
{
    $role = Role::findOrCreate('Admin', 'web');
    foreach ($permissions as $name) {
        $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
    }

    $user = User::factory()->create($attrs);
    $user->assignRole($role);

    // The permission cache is primed empty at app boot; refresh it so the
    // Gate sees the abilities created above (mirrors a seeded environment).
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('renders the login screen for guests', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee(__('admin.login.title'));
});

it('redirects guests to the admin login', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('does not let a logged-in customer reach the admin', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get('/admin')
        ->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});

it('lets an active admin with permission open the dashboard', function () {
    $admin = makeAdmin();

    $this->actingAs($admin, 'admin')
        ->get('/admin')
        ->assertOk()
        ->assertSee($admin->name);
});

it('forbids an admin without the dashboard permission', function () {
    $admin = User::factory()->create(); // no role / no permission

    $this->actingAs($admin, 'admin')
        ->get('/admin')
        ->assertForbidden();
});

it('signs in with valid credentials and records the login time', function () {
    $admin = makeAdmin();

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue()
        ->and($admin->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password and shows remaining attempts', function () {
    $admin = makeAdmin();

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email')
        ->assertSet('attemptsLeft', 4);

    expect(auth('admin')->check())->toBeFalse();
});

it('does not sign in a deactivated admin', function () {
    $admin = makeAdmin(attrs: ['is_active' => false]);

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('admin')->check())->toBeFalse();
});

it('locks the account after five failed attempts', function () {
    $admin = makeAdmin();

    foreach (range(1, 5) as $i) {
        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'nope')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'password') // correct now, but locked
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('admin')->check())->toBeFalse();
});

it('logs the admin out', function () {
    $admin = makeAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});

it('switches the admin language in the session', function () {
    $this->get(route('admin.lang', 'en'));

    expect(session('admin_locale'))->toBe('en');
});

it('sends a reset link pointing at the admin reset route', function () {
    Notification::fake();
    $admin = makeAdmin();

    Livewire::test(ForgotPassword::class)
        ->set('email', $admin->email)
        ->call('send')
        ->assertSet('sent', true);

    Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
});

it('does not reveal whether an unknown email exists', function () {
    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody@dots.test')
        ->call('send')
        ->assertSet('sent', true)
        ->assertHasNoErrors();

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $admin = makeAdmin();
    $token = Password::broker('users')->createToken($admin);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $admin->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.login'));

    expect(auth('admin')->attempt(['email' => $admin->email, 'password' => 'new-password']))->toBeTrue();
});
