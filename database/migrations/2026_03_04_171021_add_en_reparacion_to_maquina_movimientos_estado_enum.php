<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE maquina_movimientos
            MODIFY estado_anterior ENUM('operativa','fuera_servicio','en_reparacion','baja_definitiva') NULL
        ");

        DB::statement("
            ALTER TABLE maquina_movimientos
            MODIFY estado_nuevo ENUM('operativa','fuera_servicio','en_reparacion','baja_definitiva') NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE maquina_movimientos
            MODIFY estado_anterior ENUM('operativa','fuera_servicio','baja_definitiva') NULL
        ");

        DB::statement("
            ALTER TABLE maquina_movimientos
            MODIFY estado_nuevo ENUM('operativa','fuera_servicio','baja_definitiva') NULL
        ");
    }
};