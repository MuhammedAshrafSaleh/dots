<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

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
