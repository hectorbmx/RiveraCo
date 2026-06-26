<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // === Permisos ===
        $permisos = [
            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles
            'roles.view',
            'roles.assign',

            // Clientes
            'clientes.view',
            'clientes.create',
            'clientes.edit',
            'clientes.delete',

            // Obras
            'obras.view',
            'obras.create',
            'obras.edit',
            'obras.delete',

            // Detalles de obra
            'obra-detalles.view',
            'obra-detalles.create',
            'obra-detalles.edit',
            'obra-detalles.delete',

            // Borradores de factura de obra
            'obra_factura_borradores.view.access',
            'obra_factura_borradores.create.access',
            'obra_factura_borradores.edit.access',
            'obra_factura_borradores.print.access',
            'obra_factura_borradores.authorize.access',
            'obra_factura_borradores.reject.access',
            'obra_factura_borradores.invoice.access',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // === Roles ===
        $super = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin-rivera', 'guard_name' => 'web']);
        $jefe  = Role::firstOrCreate(['name' => 'jefe-obra', 'guard_name' => 'web']);
        $sup   = Role::firstOrCreate(['name' => 'supervisor-obra', 'guard_name' => 'web']);
        $cons  = Role::firstOrCreate(['name' => 'consulta', 'guard_name' => 'web']);

        // Super admin: todos los permisos
        $super->syncPermissions(Permission::all());

        // Admin Rivera → permisos explícitos (sin comodines)
        $admin->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',

            'clientes.view',
            'clientes.create',
            'clientes.edit',
            'clientes.delete',

            'obras.view',
            'obras.create',
            'obras.edit',
            'obras.delete',

            'obra-detalles.view',
            'obra-detalles.create',
            'obra-detalles.edit',
            'obra-detalles.delete',

            'obra_factura_borradores.view.access',
            'obra_factura_borradores.create.access',
            'obra_factura_borradores.edit.access',
            'obra_factura_borradores.print.access',
            'obra_factura_borradores.authorize.access',
            'obra_factura_borradores.reject.access',
            'obra_factura_borradores.invoice.access',
        ]);

        // Jefe de obra
        $jefe->syncPermissions([
            'clientes.view',

            'obras.view',
            'obras.edit',

            'obra-detalles.view',
            'obra-detalles.create',
            'obra-detalles.edit',

            'obra_factura_borradores.view.access',
            'obra_factura_borradores.create.access',
            'obra_factura_borradores.edit.access',
            'obra_factura_borradores.print.access',
        ]);

        // Supervisor de obra
        $sup->syncPermissions([
            'clientes.view',

            'obras.view',

            'obra-detalles.view',
            'obra-detalles.create',
        ]);

        // Solo consulta
        $cons->syncPermissions([
            'clientes.view',
            'obras.view',
        ]);
    }
}
