<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Renombrar registros existentes de "vobo" -> "vobo_1"
        // El esquema no cambia: campo es string(80) y UNIQUE(documento, campo)
        // ya soporta los nuevos valores como filas independientes.
        DB::table('documento_firmantes')
            ->where('campo', 'vobo')
            ->update(['campo' => 'vobo_1']);
    }

    public function down(): void
    {
        // Revertir: vobo_1 -> vobo
        DB::table('documento_firmantes')
            ->where('campo', 'vobo_1')
            ->update(['campo' => 'vobo']);

        // Eliminar cualquier vobo_2 que se haya creado
        DB::table('documento_firmantes')
            ->where('campo', 'vobo_2')
            ->delete();
    }
};
