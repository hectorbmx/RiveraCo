<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'obras.empleados.rol.edit.access',
            'guard_name' => 'web',
        ]);

        Role::whereIn('name', ['super-admin', 'admin-rivera', 'admin'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'obras.empleados.rol.edit.access')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            Role::whereIn('name', ['super-admin', 'admin-rivera', 'admin'])
                ->where('guard_name', 'web')
                ->get()
                ->each(fn (Role $role) => $role->revokePermissionTo($permission));

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};