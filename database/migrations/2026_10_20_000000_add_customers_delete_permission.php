<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'customers.delete',
            'guard_name' => 'web',
        ]);

        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin) {
            $admin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('name', 'customers.delete')->where('guard_name', 'web')->delete();
    }
};
