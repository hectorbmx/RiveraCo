<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // Comisionables
            ['rol_key' => 'PERFORADOR',            'nombre' => 'Perforador',            'comisionable' => true],
            ['rol_key' => 'AYUDANTE_PERFORADOR',   'nombre' => 'Ayudante perforador',   'comisionable' => true],
            ['rol_key' => 'BENTONITERO',            'nombre' => 'Bentonitero',            'comisionable' => true],
            ['rol_key' => 'COLADOR',                'nombre' => 'Colador',                'comisionable' => true],

            // No comisionables
            ['rol_key' => 'RESIDENTE',              'nombre' => 'Residente',              'comisionable' => false],
            ['rol_key' => 'SUPERVISOR_OBRA',        'nombre' => 'Supervisor de obra',     'comisionable' => false],
            ['rol_key' => 'CHOFER',                 'nombre' => 'Chofer',                 'comisionable' => false],
            ['rol_key' => 'AYUDANTE_GENERAL',       'nombre' => 'Ayudante general',       'comisionable' => false],
            ['rol_key' => 'SOLDADOR',               'nombre' => 'Soldador',               'comisionable' => false],
            ['rol_key' => 'MECANICO',               'nombre' => 'Mecánico',               'comisionable' => false],
            ['rol_key' => 'ALMACENISTA',            'nombre' => 'Almacenista',            'comisionable' => false],
            ['rol_key' => 'AUX_ADMIN',              'nombre' => 'Auxiliar administrativo','comisionable' => false],
            ['rol_key' => 'INGENIERO',              'nombre' => 'Ingeniero',              'comisionable' => false],
            ['rol_key' => 'OFICIAL',                'nombre' => 'Oficial',                'comisionable' => false],
            ['rol_key' => 'GERENTE_ALMACEN',        'nombre' => 'Gerente de almacen',     'comisionable' => false],
            ['rol_key' => 'GERENTE_ADMINISTRATIVO', 'nombre' => 'Gerente administrativo',  'comisionable' => false],
            ['rol_key' => 'ENCARGADO_ALMACEN',      'nombre' => 'Encargado de almacen',   'comisionable' => false],
            ['rol_key' => 'AUXILIAR_ALMACEN',       'nombre' => 'Auxiliar de almacen',    'comisionable' => false],
            ['rol_key' => 'PRODUCCION_ALMACEN',     'nombre' => 'Produccion almacen',     'comisionable' => false],
            ['rol_key' => 'CONSULTA_ALMACEN',       'nombre' => 'Consulta almacen',       'comisionable' => false],
            ['rol_key' => 'OTRO',                   'nombre' => 'Otro',                   'comisionable' => false],
        ];

        foreach ($roles as $role) {
            DB::table('catalogo_roles')->updateOrInsert(
                ['rol_key' => $role['rol_key']],
                $role
            );
        }
    }
}

