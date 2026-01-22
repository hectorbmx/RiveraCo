<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmpresaConfig;

class EmpresaConfigSeeder extends Seeder
{
    public function run(): void
    {
        EmpresaConfig::firstOrCreate(
            ['id' => 1],
            [
                'razon_social'     => 'Rivera Construcciones',
                'nombre_comercial' => 'Rivera',
                'moneda_base'      => 'MXN',
                'iva_por_defecto'  => 16.00,
                'activa'           => true,
            ]
        );
    }
}
