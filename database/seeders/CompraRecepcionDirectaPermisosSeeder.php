<?php

namespace Database\Seeders;

use App\Models\CompraRecepcionDirecta;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CompraRecepcionDirectaPermisosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([CompraRecepcionDirecta::PERMISO_VER, CompraRecepcionDirecta::PERMISO_CONFIRMAR, CompraRecepcionDirecta::PERMISO_REVERTIR] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
