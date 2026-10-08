<?php

use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('creates every configured permission and an Admin role holding all of them', function () {
    $this->seed(RolesAndAdminSeeder::class);

    $names = config('permissions');

    expect(Permission::count())->toBe(count($names));

    $admin = Role::where('name', 'Admin')->firstOrFail();
    expect($admin->permissions()->count())->toBe(count($names));
});

it('seeds an admin user with the Admin role and all abilities', function () {
    $this->seed(RolesAndAdminSeeder::class);

    $user = User::where('email', 'admin@dots.test')->firstOrFail();

    expect($user->hasRole('Admin'))->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->can('products.publish'))->toBeTrue()
        ->and($user->can('settings.update'))->toBeTrue();
});

it('is idempotent across repeated runs', function () {
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(RolesAndAdminSeeder::class);

    expect(Permission::count())->toBe(count(config('permissions')))
        ->and(Role::where('name', 'Admin')->count())->toBe(1)
        ->and(User::where('email', 'admin@dots.test')->count())->toBe(1);
});
