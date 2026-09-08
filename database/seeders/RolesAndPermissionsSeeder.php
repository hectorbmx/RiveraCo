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
            'obra_factura_borradores.revoke_authorization.access',
            'obra_factura_borradores.invoice.access',

            // Nomina - corridas
            'nomina.corridas.close.access',
            'nomina.corridas.pay.access',
            'nomina.corridas.reopen.access',
            'nomina.corridas.delete.access',

            // Huentitan
            'huentitan.access',
            'huentitan.ordenes_fabricacion.create',
            'huentitan.entradas.view',
            'huentitan.entradas.create',
            'huentitan.entradas.apply',
            'huentitan.entradas.cancel',
            'huentitan.salidas.view',
            'huentitan.salidas.create',
            'huentitan.salidas.apply',
            'huentitan.salidas.cancel',


            // Reposicion de caja chica
            'caja_chica.view',
            'caja_chica.create',
            'caja_chica.edit_own_draft',
            'caja_chica.submit',
            'caja_chica.review',
            'caja_chica.authorize',
            'caja_chica.reject',
            'caja_chica.return',
            'caja_chica.relations.view',
            'caja_chica.relations.generate',
            'caja_chica.relations.pdf',
            'caja_chica.program',
            'caja_chica.pay',
            'caja_chica.catalogs.manage',
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

        $huentitanGerenteAlmacen = Role::firstOrCreate(['name' => 'huentitan-gerente-almacen', 'guard_name' => 'web']);
        $huentitanGerenteAdministrativo = Role::firstOrCreate(['name' => 'huentitan-gerente-administrativo', 'guard_name' => 'web']);
        $huentitanEncargadoAlmacen = Role::firstOrCreate(['name' => 'huentitan-encargado-almacen', 'guard_name' => 'web']);
        $huentitanAuxiliarAlmacen = Role::firstOrCreate(['name' => 'huentitan-auxiliar-almacen', 'guard_name' => 'web']);
        $huentitanProduccionAlmacen = Role::firstOrCreate(['name' => 'huentitan-produccion-almacen', 'guard_name' => 'web']);
        $huentitanConsultaAlmacen = Role::firstOrCreate(['name' => 'huentitan-consulta-almacen', 'guard_name' => 'web']);

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
            'obra_factura_borradores.revoke_authorization.access',
            'obra_factura_borradores.invoice.access',

            'nomina.corridas.close.access',
            'nomina.corridas.pay.access',
            'nomina.corridas.reopen.access',
            'nomina.corridas.delete.access',

            'caja_chica.view',
            'caja_chica.create',
            'caja_chica.edit_own_draft',
            'caja_chica.submit',
            'caja_chica.review',
            'caja_chica.authorize',
            'caja_chica.reject',
            'caja_chica.return',
            'caja_chica.relations.view',
            'caja_chica.relations.generate',
            'caja_chica.relations.pdf',
            'caja_chica.program',
            'caja_chica.pay',
            'caja_chica.catalogs.manage',
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

        $huentitanViewPermissions = [
            'huentitan.access',
            'huentitan.empleados.view',
            'huentitan.productos.view',
            'huentitan.inventario.view',
            'huentitan.ordenes_compra.view',
            'huentitan.ordenes_fabricacion.view',
            'huentitan.entradas.view',
            'huentitan.entradas.create',
            'huentitan.entradas.apply',
            'huentitan.entradas.cancel',
            'huentitan.salidas.view',
            'huentitan.salidas.create',
            'huentitan.salidas.apply',
            'huentitan.salidas.cancel',
        ];

        $huentitanGerenteAlmacen->syncPermissions($huentitanViewPermissions);
        $huentitanGerenteAdministrativo->syncPermissions($huentitanViewPermissions);
        $huentitanEncargadoAlmacen->syncPermissions([
            'huentitan.access',
            'huentitan.empleados.view',
            'huentitan.productos.view',
            'huentitan.inventario.view',
            'huentitan.ordenes_fabricacion.view',
            'huentitan.entradas.view',
            'huentitan.entradas.create',
            'huentitan.entradas.apply',
            'huentitan.entradas.cancel',
            'huentitan.salidas.view',
            'huentitan.salidas.create',
            'huentitan.salidas.apply',
            'huentitan.salidas.cancel',
        ]);
        $huentitanAuxiliarAlmacen->syncPermissions([
            'huentitan.access',
            'huentitan.productos.view',
            'huentitan.inventario.view',
            'huentitan.entradas.view',
            'huentitan.entradas.create',
            'huentitan.salidas.view',
            'huentitan.salidas.create',
        ]);

        $huentitanProduccionAlmacen->syncPermissions([
            'huentitan.access',
            'huentitan.productos.view',
            'huentitan.ordenes_fabricacion.view',
        ]);
        $huentitanConsultaAlmacen->syncPermissions([
            'huentitan.access',
            'huentitan.empleados.view',
            'huentitan.productos.view',
            'huentitan.inventario.view',
            'huentitan.salidas.view',
        ]);
    }
}





