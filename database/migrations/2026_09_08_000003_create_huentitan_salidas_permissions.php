<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permisos = [
        'huentitan.salidas.view',
        'huentitan.salidas.create',
        'huentitan.salidas.apply',
        'huentitan.salidas.cancel',
    ];

    public function up(): void
    {
        foreach ($this->permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $rolesHuentitan = [
            'super-admin',
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
            $auxiliar->givePermissionTo(['huentitan.salidas.view', 'huentitan.salidas.create']);
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
