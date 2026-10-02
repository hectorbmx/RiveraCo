<?php

namespace Database\Seeders;

use App\Models\NominaTipoSueldo;
use Illuminate\Database\Seeder;

class NominaTipoSueldoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'id' => 1,
                'codigo' => 'semanal',
                'nombre' => 'Semanal',
                'dias_periodo' => 7,
                'factor_mensual' => 4.3333,
                'activo' => true,
                'orden' => 1,
            ],
            [
                'id' => 2,
                'codigo' => 'quincenal',
                'nombre' => 'Quincenal',
                'dias_periodo' => 15,
                'factor_mensual' => 2.0000,
                'activo' => true,
                'orden' => 2,
            ],
            [
                'id' => 3,
                'codigo' => 'mensual',
                'nombre' => 'Mensual',
                'dias_periodo' => 30,
                'factor_mensual' => 1.0000,
                'activo' => true,
                'orden' => 3,
            ],
        ];

        foreach ($tipos as $tipo) {
            NominaTipoSueldo::updateOrCreate(
                ['id' => $tipo['id']],
                $tipo
            );
        }
    }
}
