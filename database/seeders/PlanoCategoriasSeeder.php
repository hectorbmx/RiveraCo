<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanoCategoria;

class PlanoCategoriasSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Arquitectónico',
            'Estructural',
            'Hidrosanitario',
            'Eléctrico',
            'Topografía',
            'Urbanización',
            'Instalaciones especiales',
        ];

        foreach ($categorias as $cat) {
            PlanoCategoria::firstOrCreate(['nombre' => $cat]);
        }
    }
}
