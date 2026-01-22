<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComisionesCatalogosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // =========================
            // 1) UoMs
            // =========================
            $uoms = [
                ['clave' => 'ML',  'nombre' => 'Metro lineal',  'simbolo' => 'ml',  'tipo' => 'longitud', 'activa' => 1],
                ['clave' => 'M3',  'nombre' => 'Metro cúbico',  'simbolo' => 'm³',  'tipo' => 'volumen',  'activa' => 1],
                ['clave' => 'KG',  'nombre' => 'Kilogramo',     'simbolo' => 'kg',  'tipo' => 'peso',     'activa' => 1],
                ['clave' => 'PZA', 'nombre' => 'Pieza',         'simbolo' => 'pza', 'tipo' => 'cantidad', 'activa' => 1],
                ['clave' => 'H',   'nombre' => 'Hora',          'simbolo' => 'h',   'tipo' => 'tiempo',   'activa' => 1],
            ];

            foreach ($uoms as $row) {
                DB::table('uoms')->updateOrInsert(
                    ['clave' => $row['clave']],
                    array_merge($row, [
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ])
                );
            }

            $uomML  = DB::table('uoms')->where('clave', 'ML')->value('id');
            $uomM3  = DB::table('uoms')->where('clave', 'M3')->value('id');
            $uomKG  = DB::table('uoms')->where('clave', 'KG')->value('id');
            $uomPZA = DB::table('uoms')->where('clave', 'PZA')->value('id');

            // =========================
            // 2) Trabajo: PERFORACION
            // =========================
            DB::table('catalogo_trabajos_comision')->updateOrInsert(
                ['key' => 'PERFORACION'],
                [
                    'nombre' => 'Perforación',
                    'activo' => 1,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );

            // =========================
            // 3) Actividades comisionables (para checks UI)
            // key debe coincidir con columnas en comision_detalles
            // =========================
            $actividades = [
                ['key' => 'metros_sujetos_comision', 'nombre' => 'Metros sujetos a comisión', 'uom_id' => $uomML,  'orden' => 10, 'activa' => 1],
                ['key' => 'kg_acero',               'nombre' => 'Acero',                     'uom_id' => $uomKG,  'orden' => 20, 'activa' => 1],
                ['key' => 'vol_bentonita',          'nombre' => 'Bentonita',                 'uom_id' => $uomM3,  'orden' => 30, 'activa' => 1],
                ['key' => 'vol_concreto',           'nombre' => 'Concreto',                  'uom_id' => $uomM3,  'orden' => 40, 'activa' => 1],
                ['key' => 'campana_pzas',           'nombre' => 'Campana',                   'uom_id' => $uomPZA, 'orden' => 50, 'activa' => 1],
            ];

            foreach ($actividades as $row) {
                DB::table('catalogo_actividades_comision')->updateOrInsert(
                    ['key' => $row['key']],
                    array_merge($row, [
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ])
                );
            }
        });
    }
}
