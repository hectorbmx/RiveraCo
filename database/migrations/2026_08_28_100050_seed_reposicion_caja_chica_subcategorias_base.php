<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categorias = DB::table('reposicion_caja_chica_categorias')->get(['id', 'codigo']);

        $subcategorias = [
            ['codigo' => 'servicio', 'nombre' => 'Servicio', 'orden' => 1],
            ['codigo' => 'consumibles', 'nombre' => 'Consumibles', 'orden' => 2],
            ['codigo' => 'refacciones', 'nombre' => 'Refacciones', 'orden' => 3],
            ['codigo' => 'mantenimientos', 'nombre' => 'Mantenimientos', 'orden' => 4],
        ];

        foreach ($categorias as $categoria) {
            foreach ($subcategorias as $subcategoria) {
                DB::table('reposicion_caja_chica_subcategorias')->updateOrInsert(
                    [
                        'categoria_id' => $categoria->id,
                        'codigo' => $subcategoria['codigo'],
                    ],
                    [
                        'nombre' => $subcategoria['nombre'],
                        'descripcion' => null,
                        'activo' => true,
                        'orden' => $subcategoria['orden'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('reposicion_caja_chica_subcategorias')
            ->whereIn('codigo', ['servicio', 'consumibles', 'refacciones', 'mantenimientos'])
            ->delete();
    }
};
