<?php

namespace Database\Seeders;

use App\Models\Maquina;
use Illuminate\Database\Seeder;

class MaquinaSeeder extends Seeder
{
    public function run(): void
    {
        $maquinas = [
            'B-1',
            'B-2',
            'B-3',
            'B-4',
            'B-5',
            'B-6',
            'B-7',
            'B-8',
            'C-1',
            'C-2',
            'S-1',
            'S-2',
            'S-3',
            'BG-9',
            'BG-12V',
            'BG-12H',
            'BG-18R',
            'BG-25C',
            'GRUA TEREX',
            'RETRO',
            'bg123',
        ];

        foreach ($maquinas as $codigo) {
            Maquina::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre'      => null,
                    'tipo'        => null,
                    'marca'       => null,
                    'modelo'      => null,
                    'numero_serie'=> null,
                    'disponible'  => true,
                    'activo'      => true,
                    'notas'       => null,
                ]
            );
        }
    }
}
