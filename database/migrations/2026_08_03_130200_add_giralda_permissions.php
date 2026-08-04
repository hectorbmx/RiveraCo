<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    private array $permissions = [
        'giralda.access',
        'giralda.horas_extras.authorize.access',
        'empleados.epp.access',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $permissions = Permission::whereIn('name', $this->permissions)->get();

        Role::query()
            ->whereIn('name', ['Super Admin', 'Admin Rivera', 'Administrador', 'admin'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
