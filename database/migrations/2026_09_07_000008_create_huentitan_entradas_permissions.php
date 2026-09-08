<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permisos = [
        'huentitan.entradas.view',
        'huentitan.entradas.create',
        'huentitan.entradas.apply',
        'huentitan.entradas.cancel',
    ];

    public function up(): void
    {
        foreach ($this->permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        // Asignar a super-admin
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($this->permisos);
        }

        // Asignar a roles de almacen Huentitan
        $rolesHuentitan = [
            'admin-rivera',
            'huentitan-gerente-almacen',
            'huentitan-gerente-administrativo',
            'huentitan-encargado-almacen',
        ];

        foreach ($rolesHuentitan as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($this->permisos);
            }
        }

        $auxiliar = Role::where('name', 'huentitan-auxiliar-almacen')->where('guard_name', 'web')->first();
        if ($auxiliar) {
            $auxiliar->givePermissionTo(['huentitan.entradas.view', 'huentitan.entradas.create']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permisos)
            ->where('guard_name', 'web')
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
