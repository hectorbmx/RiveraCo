<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reposicion_caja_chica_categorias')->updateOrInsert(
            ['codigo' => 'transferencia_factura'],
            [
                'nombre' => 'Con transferencia y factura',
                'descripcion' => 'Gasto pagado por transferencia con comprobante fiscal.',
                'requiere_factura' => true,
                'requiere_xml' => true,
                'forma_pago_base' => 'transferencia',
                'activo' => true,
                'orden' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $categoriaId = DB::table('reposicion_caja_chica_categorias')
            ->where('codigo', 'transferencia_factura')
            ->value('id');

        if (!$categoriaId) {
            return;
        }

        $subcategorias = [
            ['codigo' => 'servicio', 'nombre' => 'Servicio', 'orden' => 1],
            ['codigo' => 'consumibles', 'nombre' => 'Consumibles', 'orden' => 2],
            ['codigo' => 'refacciones', 'nombre' => 'Refacciones', 'orden' => 3],
            ['codigo' => 'mantenimientos', 'nombre' => 'Mantenimientos', 'orden' => 4],
        ];

        foreach ($subcategorias as $subcategoria) {
            DB::table('reposicion_caja_chica_subcategorias')->updateOrInsert(
                [
                    'categoria_id' => $categoriaId,
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

    public function down(): void
    {
        $categoriaId = DB::table('reposicion_caja_chica_categorias')
            ->where('codigo', 'transferencia_factura')
            ->value('id');

        if ($categoriaId) {
            DB::table('reposicion_caja_chica_subcategorias')
                ->where('categoria_id', $categoriaId)
                ->delete();
        }

        DB::table('reposicion_caja_chica_categorias')
            ->where('codigo', 'transferencia_factura')
            ->delete();
    }
};