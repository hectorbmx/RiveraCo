<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('empresa_servicio_preventivo_tipos')
            ->where('ambito', 'maquinaria')
            ->where('codigo', 'servicio_preventivo')
            ->exists();

        if (!$exists) {
            DB::table('empresa_servicio_preventivo_tipos')->insert([
                'ambito' => 'maquinaria',
                'codigo' => 'servicio_preventivo',
                'nombre' => 'Servicio preventivo',
                'unidad' => 'horas',
                'intervalo_valor' => 250,
                'intervalo_meses' => 6,
                'alerta_valor' => 20,
                'alerta_dias' => null,
                'activo' => true,
                'orden' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('empresa_servicio_preventivo_tipos')
            ->where('ambito', 'maquinaria')
            ->where('codigo', 'servicio_preventivo')
            ->delete();
    }
};
