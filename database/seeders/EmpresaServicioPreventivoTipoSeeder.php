<?php

namespace Database\Seeders;

use App\Models\EmpresaServicioPreventivoTipo;
use Illuminate\Database\Seeder;

class EmpresaServicioPreventivoTipoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'ambito' => EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
                'codigo' => 'basico',
                'nombre' => 'Basico',
                'unidad' => EmpresaServicioPreventivoTipo::UNIDAD_HORAS,
                'intervalo_valor' => 250,
                'intervalo_meses' => 6,
                'alerta_valor' => 20,
                'alerta_dias' => null,
                'activo' => true,
                'orden' => 1,
            ],
            [
                'ambito' => EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
                'codigo' => 'general',
                'nombre' => 'General',
                'unidad' => EmpresaServicioPreventivoTipo::UNIDAD_HORAS,
                'intervalo_valor' => 1000,
                'intervalo_meses' => 6,
                'alerta_valor' => 50,
                'alerta_dias' => null,
                'activo' => true,
                'orden' => 2,
            ],
            [
                'ambito' => EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
                'codigo' => 'mayor',
                'nombre' => 'Mayor',
                'unidad' => EmpresaServicioPreventivoTipo::UNIDAD_HORAS,
                'intervalo_valor' => 3000,
                'intervalo_meses' => 12,
                'alerta_valor' => 100,
                'alerta_dias' => null,
                'activo' => true,
                'orden' => 3,
            ],
            [
                'ambito' => EmpresaServicioPreventivoTipo::AMBITO_VEHICULO,
                'codigo' => 'basico',
                'nombre' => 'Basico',
                'unidad' => EmpresaServicioPreventivoTipo::UNIDAD_KM,
                'intervalo_valor' => 5000,
                'intervalo_meses' => 6,
                'alerta_valor' => 500,
                'alerta_dias' => 10,
                'activo' => true,
                'orden' => 1,
            ],
        ];

        foreach ($tipos as $tipo) {
            EmpresaServicioPreventivoTipo::updateOrCreate(
                [
                    'ambito' => $tipo['ambito'],
                    'codigo' => $tipo['codigo'],
                ],
                $tipo
            );
        }
    }
}
