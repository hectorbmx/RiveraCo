<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'registrado_por')) {
                $table->foreignId('registrado_por')
                    ->nullable()
                    ->after('usuario_registro')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ordenes_compra', 'autorizado_por')) {
                $table->foreignId('autorizado_por')
                    ->nullable()
                    ->after('usuario_autoriza')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $permissions = [
            'ordenes_compra.view.access',
            'ordenes_compra.create.access',
            'ordenes_compra.edit.access',
            'ordenes_compra.print.access',
            'ordenes_compra.authorize.access',
            'ordenes_compra.cancel.access',
            'pagos_proveedores.view.access',
            'pagos_proveedores.schedule.access',
            'pagos_proveedores.authorize.access',
            'pagos_proveedores.pay.access',
            'pagos_proveedores.cancel.access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->assignPermissions();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'autorizado_por')) {
                $table->dropConstrainedForeignId('autorizado_por');
            }

            if (Schema::hasColumn('ordenes_compra', 'registrado_por')) {
                $table->dropConstrainedForeignId('registrado_por');
            }
        });
    }

    private function assignPermissions(): void
    {
        $super = Role::where('name', 'super-admin')->first();
        if ($super) {
            $super->givePermissionTo(Permission::whereIn('name', [
                'ordenes_compra.view.access',
                'ordenes_compra.create.access',
                'ordenes_compra.edit.access',
                'ordenes_compra.print.access',
                'ordenes_compra.authorize.access',
                'ordenes_compra.cancel.access',
                'pagos_proveedores.view.access',
                'pagos_proveedores.schedule.access',
                'pagos_proveedores.authorize.access',
                'pagos_proveedores.pay.access',
                'pagos_proveedores.cancel.access',
            ])->get());
        }

        $adminRivera = Role::where('name', 'admin-rivera')->first();
        if ($adminRivera) {
            $adminRivera->givePermissionTo(Permission::whereIn('name', [
                'ordenes_compra.view.access',
                'ordenes_compra.create.access',
                'ordenes_compra.edit.access',
                'ordenes_compra.print.access',
                'ordenes_compra.authorize.access',
                'ordenes_compra.cancel.access',
                'pagos_proveedores.view.access',
                'pagos_proveedores.schedule.access',
            ])->get());
        }

        $secretaria = Role::where('name', 'secretaria')->first();
        if ($secretaria) {
            $secretaria->givePermissionTo(Permission::whereIn('name', [
                'ordenes_compra.view.access',
                'ordenes_compra.create.access',
                'ordenes_compra.edit.access',
                'ordenes_compra.print.access',
                'pagos_proveedores.view.access',
                'pagos_proveedores.schedule.access',
            ])->get());
        }
    }
};
