<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
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
        $password = $this->adminPassword();

        $user = User::firstOrNew(['email' => $email]);
        $user->name ??= 'DOTS Admin';
        $user->is_active = true;
        $user->password = Hash::make($password); // kept in sync with the configured value
        $user->save();
        $user->syncRoles([$admin]);

        $this->command->info("Admin ready: {$email}");
    }

    /**
     * The admin password. In production it MUST come from ADMIN_PASSWORD and
     * seeding fails loudly if it is empty — there is no default outside local.
     */
    private function adminPassword(): string
    {
        $password = config('dots.admin.password');

        if (is_string($password) && $password !== '') {
            return $password;
        }

        if (app()->isProduction()) {
            throw new RuntimeException('ADMIN_PASSWORD must be set to seed the admin user in production.');
        }

        return 'password';
    }
}
