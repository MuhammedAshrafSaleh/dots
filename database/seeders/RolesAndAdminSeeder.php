<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    /** Idempotent: safe to run repeatedly. */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var array<int, string> $names */
        $names = config('permissions');

        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('Admin', 'web');
        $admin->syncPermissions($names);

        $email = (string) config('dots.admin.email');
        $password = (string) config('dots.admin.password');

        $user = User::firstOrNew(['email' => $email]);
        $user->name ??= 'DOTS Admin';
        $user->is_active = true;
        $user->password = Hash::make($password); // kept in sync with the configured value
        $user->save();
        $user->syncRoles([$admin]);

        $this->command->info("Admin ready: {$email}");
    }
}
