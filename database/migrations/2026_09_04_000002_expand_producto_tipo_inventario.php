<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('productos', 'tipo_inventario')) {
            DB::statement("ALTER TABLE productos MODIFY tipo_inventario VARCHAR(40) NOT NULL DEFAULT 'materia_prima'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'tipo_inventario')) {
            DB::statement("ALTER TABLE productos MODIFY tipo_inventario ENUM('consumible','herramienta') NOT NULL DEFAULT 'consumible'");
        }
    }
};