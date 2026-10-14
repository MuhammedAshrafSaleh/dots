<?php

// Safety guards wired into beforeEach so they fire after PHPUnit processes <php><env>
// entries from phpunit.xml — unit tests do not extend TestCase so this is the only
// coverage for them. Feature/Browser tests also get TestCase::setUpTraits() which
// adds a post-boot config() check before any DB trait runs.
pest()->beforeEach(function (): void {
    if (file_exists(dirname(__DIR__).'/bootstrap/cache/config.php')) {
        throw new RuntimeException(
            "SAFETY ABORT: bootstrap/cache/config.php exists.\n".
            "Run `php artisan config:clear` then retry.\n".
            'A cached config ignores phpunit.xml env entries and may target the wrong database.'
        );
    }

    $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');
    if ($appEnv !== 'testing') {
        throw new RuntimeException(
            "SAFETY ABORT: APP_ENV is '{$appEnv}', expected 'testing'."
        );
    }

    $dbName = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
    if (! preg_match('/^dots_testing(_test_\d+)?$/', $dbName)) {
        throw new RuntimeException(
            "SAFETY ABORT: DB_DATABASE env is '{$dbName}', expected 'dots_testing'.\n".
            'Tests must not touch the dev database.'
        );
    }
});

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\BrowserTestCase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Browser (Pest 4 + Playwright) tests drive the app over a real HTTP server
// whose DB connection does not see the test's open transaction, so they need
// committed data — truncate (not transaction-wrap) between tests.
// BrowserTestCase excludes Spatie lookup tables from truncation to prevent
// InnoDB deadlocks between the test process and the HTTP-server process.
// beforeEach pre-seeds permissions via a single INSERT IGNORE (no per-row
// gap locks) before any visit() call starts the HTTP server, so adminUser()
// always finds existing rows and never races against a live server read.
pest()->extend(BrowserTestCase::class)
    ->beforeEach(function () {
        $now = now();
        Permission::insertOrIgnore(
            collect(config('permissions'))->map(fn ($n) => [
                'name' => $n,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    })
    ->in('Browser');

/**
 * Create an active admin holding the given permissions (defaults to every
 * configured permission). Each admin gets its own role so permission scoping
 * is independent per test. Flushes the spatie cache so the Gate sees the
 * freshly-created abilities.
 *
 * @param  array<int, string>|null  $permissions
 */
function adminUser(?array $permissions = null): User
{
    $names = $permissions ?? config('permissions');

    $role = Role::create(['name' => 'role-'.Str::random(8), 'guard_name' => 'web']);
    foreach ($names as $name) {
        $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
    }

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

/**
 * Act as an admin holding the given permissions on the `admin` guard.
 *
 * @param  array<int, string>|null  $permissions
 */
function loginAdmin(?array $permissions = null): User
{
    $user = adminUser($permissions);
    test()->actingAs($user, 'admin');

    return $user;
}
