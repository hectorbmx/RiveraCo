<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mantenimientos MODIFY estatus ENUM('borrador', 'orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado') NOT NULL DEFAULT 'borrador'");
    }

    public function down(): void
    {
        DB::statement("UPDATE mantenimientos SET estatus = 'pendiente' WHERE estatus = 'orden_generada'");
        DB::statement("ALTER TABLE mantenimientos MODIFY estatus ENUM('borrador', 'pendiente', 'en_proceso', 'completado', 'cancelado') NOT NULL DEFAULT 'borrador'");
    }
};
