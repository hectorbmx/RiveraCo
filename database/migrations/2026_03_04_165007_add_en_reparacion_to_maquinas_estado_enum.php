<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar 'en_reparacion' al enum (manteniendo los existentes)
        DB::statement("
            ALTER TABLE maquinas
            MODIFY COLUMN estado ENUM('operativa','fuera_servicio','en_reparacion','baja_definitiva')
            NOT NULL
            DEFAULT 'operativa'
        ");
    }

    public function down(): void
    {
        // Revertir quitando 'en_reparacion'
        // ⚠️ Si existen registros con estado='en_reparacion', este down fallará.
        DB::statement("
            ALTER TABLE maquinas
            MODIFY COLUMN estado ENUM('operativa','fuera_servicio','baja_definitiva')
            NOT NULL
            DEFAULT 'operativa'
        ");
    }
};