<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComisionTarifarioInicialSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $trabajoId = DB::table('catalogo_trabajos_comision')->where('key', 'PERFORACION')->value('id');
            if (!$trabajoId) {
                $this->command?->error('No existe trabajo PERFORACION en catalogo_trabajos_comision.');
                return;
            }

            // UoMs
            $uomML = DB::table('uoms')->where('clave', 'ML')->value('id');
            $uomM3 = DB::table('uoms')->where('clave', 'M3')->value('id');
            $uomKG = DB::table('uoms')->where('clave', 'KG')->value('id');
            $uomPZ = DB::table('uoms')->where('clave', 'PZA')->value('id');
            $uomH  = DB::table('uoms')->where('clave', 'H')->value('id');

            $tarifarioId = DB::table('comision_tarifarios')->insertGetId([
                'nombre' => 'Tarifario base (Perforación)',
                'descripcion' => 'Tarifario inicial. Ajustar y después publicar.',
                'estado' => 'borrador',
                'created_by' => null,
                'published_by' => null,
                'published_at' => null,
                'vigente_desde' => null,
                'vigente_hasta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $findRolId = function (string $keyOrName) {
                // Intento por rol_key (si existe en tu tabla); si no existe la columna, lanzará excepción
                try {
                    $id = DB::table('catalogo_roles')->where('rol_key', $keyOrName)->value('id');
                    if ($id) return $id;
                } catch (\Throwable $e) {
                    // ignore
                }

                // Fallback por nombre
                return DB::table('catalogo_roles')->where('nombre', $keyOrName)->value('id');
            };

            // =========================
            // Define aquí tus tarifas base
            // (ajusta los nombres/keys a tus roles reales)
            // =========================
            $tarifas = [
                // Ejemplos de tu conversación (ajusta roles reales):
                // Perforador: $/ML y $/H extra
                [
                    'rol' => 'PERFORADOR', // rol_key o nombre
                    'concepto' => 'produccion',
                    'variable_origen' => 'metros_sujetos_comision',
                    'uom_id' => $uomML,
                    'tarifa' => 4.00,
                ],
                [
                    'rol' => 'PERFORADOR',
                    'concepto' => 'hora_extra',
                    'variable_origen' => 'tiempo_extra',
                    'uom_id' => $uomH,
                    'tarifa' => 90.00,
                ],

                // Ayudante perforador: $/ML
                [
                    'rol' => 'AYUDANTE_PERFORADOR',
                    'concepto' => 'produccion',
                    'variable_origen' => 'metros_sujetos_comision',
                    'uom_id' => $uomML,
                    'tarifa' => 3.00,
                ],

                // Bentonitero: $/M3 bentonita
                [
                    'rol' => 'BENTONITERO',
                    'concepto' => 'produccion',
                    'variable_origen' => 'vol_bentonita',
                    'uom_id' => $uomM3,
                    'tarifa' => 4.80,
                ],

                // Colador: $/M3 concreto
                [
                    'rol' => 'COLADOR',
                    'concepto' => 'produccion',
                    'variable_origen' => 'vol_concreto',
                    'uom_id' => $uomM3,
                    'tarifa' => 4.80,
                ],

                // (Opcionales) Acero y Campana: si aplican para algún rol específico
                // [
                //     'rol' => 'ARMADOR',
                //     'concepto' => 'produccion',
                //     'variable_origen' => 'kg_acero',
                //     'uom_id' => $uomKG,
                //     'tarifa' => 0.00,
                // ],
                // [
                //     'rol' => 'CAMPANERO',
                //     'concepto' => 'produccion',
                //     'variable_origen' => 'campana_pzas',
                //     'uom_id' => $uomPZ,
                //     'tarifa' => 0.00,
                // ],
            ];

            $inserted = 0;
            foreach ($tarifas as $t) {
                $rolId = $findRolId($t['rol']);

                if (!$rolId) {
                    $this->command?->warn("Rol no encontrado en catalogo_roles: {$t['rol']} (se omite tarifa {$t['variable_origen']})");
                    continue;
                }

                DB::table('comision_tarifario_detalles')->updateOrInsert(
                    [
                        'tarifario_id' => $tarifarioId,
                        'trabajo_id' => $trabajoId,
                        'rol_id' => $rolId,
                        'concepto' => $t['concepto'],
                        'variable_origen' => $t['variable_origen'],
                    ],
                    [
                        'uom_id' => $t['uom_id'],
                        'tarifa' => $t['tarifa'],
                        'activo' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $inserted++;
            }

            $this->command?->info("Tarifario borrador creado (#{$tarifarioId}). Tarifas insertadas/actualizadas: {$inserted}");
        });
    }
}
