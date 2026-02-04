<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE inventario_documentos
            MODIFY tipo ENUM('inicial','entrada','salida','ajuste','resguardo','devolucion','cancelacion')
            NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE inventario_documentos
            MODIFY tipo ENUM('inicial','entrada','salida','ajuste','resguardo','devolucion')
            NOT NULL
        ");
    }
};
