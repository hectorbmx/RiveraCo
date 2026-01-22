<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoRolesAliasSeeder extends Seeder
{
    public function run(): void
    {
        // Helper: obtener rol_id por rol_key
        $roleId = function (string $rolKey): int {
            $id = DB::table('catalogo_roles')->where('rol_key', $rolKey)->value('id');
            if (!$id) {
                throw new \RuntimeException("No existe rol_key en catalogo_roles: {$rolKey}");
            }
            return (int) $id;
        };

        /**
         * IMPORTANTE:
         * - 'alias' debe ser EXACTO como viene en tu DB (empleados.puesto / obra_empleado.puesto_en_obra),
         *   o el formato que tú decidas estandarizar.
         * - Si más adelante decides normalizar a UPPER/TRIM en el código, aquí también puedes guardar en UPPER.
         */
        $aliases = [
            // ===== PERFORADOR =====
            ['rol_key' => 'PERFORADOR', 'alias' => 'PERFORADOR'],
            ['rol_key' => 'PERFORADOR', 'alias' => 'Perforador'],
            ['rol_key' => 'PERFORADOR', 'alias' => 'perforador'],
            ['rol_key' => 'PERFORADOR', 'alias' => 'PERFORADORA'],
            ['rol_key' => 'PERFORADOR', 'alias' => 'Perforadora'],

            // ===== AYUDANTE PERFORADOR =====
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AY PERF'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYUD PERF'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYUDANTE PERF'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYUDANTE PERFORADOR'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYUD PERFORADOR'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYUDANTE PERFORACION'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYTE.PERF.'],
            ['rol_key' => 'AYUDANTE_PERFORADOR', 'alias' => 'AYUD DE PERFORADOR'],

            // ===== RESIDENTE =====
            ['rol_key' => 'RESIDENTE', 'alias' => 'RESIDENTE'],
            ['rol_key' => 'RESIDENTE', 'alias' => 'Residente'],
            ['rol_key' => 'RESIDENTE', 'alias' => 'ING RESIDENTE'],
            ['rol_key' => 'RESIDENTE', 'alias' => 'ING. RESIDENTE'],
            ['rol_key' => 'RESIDENTE', 'alias' => 'INGENIERO RESIDENTE'],
            ['rol_key' => 'RESIDENTE', 'alias' => 'RESIDENTE OBRA'],
            ['rol_key' => 'RESIDENTE', 'alias' => 'RESIDENTE DE OBRA'],

            // ===== SUPERVISOR OBRA =====
            ['rol_key' => 'SUPERVISOR_OBRA', 'alias' => 'SUPERVISOR'],
            ['rol_key' => 'SUPERVISOR_OBRA', 'alias' => 'SUPERVISOR OBRA'],
            ['rol_key' => 'SUPERVISOR_OBRA', 'alias' => 'SUPERVISOR DE OBRA'],

            // ===== CHOFER =====
            ['rol_key' => 'CHOFER', 'alias' => 'CHOFER'],
            ['rol_key' => 'CHOFER', 'alias' => 'Chofer'],
            ['rol_key' => 'CHOFER', 'alias' => 'OPERADOR (CHOFER)'],

            // ===== AYUDANTE GENERAL =====
            ['rol_key' => 'AYUDANTE_GENERAL', 'alias' => 'AYUDANTE GENERAL'],
            ['rol_key' => 'AYUDANTE_GENERAL', 'alias' => 'Ayudante general'],
            ['rol_key' => 'AYUDANTE_GENERAL', 'alias' => 'AYUDANTE'],
            ['rol_key' => 'AYUDANTE_GENERAL', 'alias' => 'Ayudante'],

            // ===== SOLDADOR =====
            ['rol_key' => 'SOLDADOR', 'alias' => 'SOLDADOR'],
            ['rol_key' => 'SOLDADOR', 'alias' => 'SOLDADOR PAILERO'],
            ['rol_key' => 'SOLDADOR', 'alias' => 'PAILERO'],

            // ===== MECANICO =====
            ['rol_key' => 'MECANICO', 'alias' => 'MECANICO'],
            ['rol_key' => 'MECANICO', 'alias' => 'MECÁNICO'],

            // ===== ALMACENISTA =====
            ['rol_key' => 'ALMACENISTA', 'alias' => 'ALMACENISTA'],

            // ===== AUX ADMIN =====
            ['rol_key' => 'AUX_ADMIN', 'alias' => 'AUX ADMV'],
            ['rol_key' => 'AUX_ADMIN', 'alias' => 'AUXILIAR ADMV'],
            ['rol_key' => 'AUX_ADMIN', 'alias' => 'AUX ADMIN'],
            ['rol_key' => 'AUX_ADMIN', 'alias' => 'AUXILIAR ADMINISTRATIVO'],
            ['rol_key' => 'AUX_ADMIN', 'alias' => 'ADMINISTRATIVO'],

            // ===== INGENIERO =====
            ['rol_key' => 'INGENIERO', 'alias' => 'INGENIERO'],
            ['rol_key' => 'INGENIERO', 'alias' => 'ING.'],
            ['rol_key' => 'INGENIERO', 'alias' => 'INGE.'],

            // ===== OFICIAL =====
            ['rol_key' => 'OFICIAL', 'alias' => 'OFICIAL'],
        ];

        foreach ($aliases as $row) {
            DB::table('catalogo_roles_alias')->updateOrInsert(
                ['alias' => $row['alias']], // UNIQUE
                [
                    'rol_id'     => $roleId($row['rol_key']),
                    'alias'      => $row['alias'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
