<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CatalogoPila;

class CatalogoPilasSeeder extends Seeder
{
    public function run(): void
    {
        $pilas = [
            ['codigo' => 'PI20',  'descripcion' => 'Perforación en 20cm de diámetro',  'diametro_cm' => 20],
            ['codigo' => 'PI25',  'descripcion' => 'Perforación en 25cm de diámetro',  'diametro_cm' => 25],
            ['codigo' => 'PI30',  'descripcion' => 'Perforación en 30cm de diámetro',  'diametro_cm' => 30],
            ['codigo' => 'PI40',  'descripcion' => 'Perforación en 40cm de diámetro',  'diametro_cm' => 40],
            ['codigo' => 'PI50',  'descripcion' => 'Perforación en 50cm de diámetro',  'diametro_cm' => 50],
            ['codigo' => 'PI60',  'descripcion' => 'Perforación en 60cm de diámetro',  'diametro_cm' => 60],
            ['codigo' => 'PI70',  'descripcion' => 'Perforación en 70cm de diámetro',  'diametro_cm' => 70],
            ['codigo' => 'PI80',  'descripcion' => 'Perforación en 80cm de diámetro',  'diametro_cm' => 80],
            ['codigo' => 'PI90',  'descripcion' => 'Perforación en 90cm de diámetro',  'diametro_cm' => 90],
            ['codigo' => 'PI100', 'descripcion' => 'Perforación en 100cm de diámetro', 'diametro_cm' => 100],
            ['codigo' => 'PI110', 'descripcion' => 'Perforación en 110cm de diámetro', 'diametro_cm' => 110],
            ['codigo' => 'PI120', 'descripcion' => 'Perforación en 120cm de diámetro', 'diametro_cm' => 120],
            ['codigo' => 'PI130', 'descripcion' => 'Perforación en 130cm de diámetro', 'diametro_cm' => 130],
            ['codigo' => 'PI140', 'descripcion' => 'Perforación en 140cm de diámetro', 'diametro_cm' => 140],
            ['codigo' => 'PI150', 'descripcion' => 'Perforación en 150cm de diámetro', 'diametro_cm' => 150],
            ['codigo' => 'PI160', 'descripcion' => 'Perforación en 160cm de diámetro', 'diametro_cm' => 160],
            ['codigo' => 'PI180', 'descripcion' => 'Perforación en 180cm de diámetro', 'diametro_cm' => 180],
            ['codigo' => 'PI200', 'descripcion' => 'Perforación en 200cm de diámetro', 'diametro_cm' => 200],
            ['codigo' => 'PI220', 'descripcion' => 'Perforación en 220cm de diámetro', 'diametro_cm' => 220],
            ['codigo' => 'PI240', 'descripcion' => 'Perforación en 240cm de diámetro', 'diametro_cm' => 240],
        ];

        foreach ($pilas as $pila) {
            CatalogoPila::updateOrCreate(
                ['codigo' => $pila['codigo']],
                $pila
            );
        }
    }
}
