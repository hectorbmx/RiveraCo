<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Crear columna nueva estatus_int para migrar sin riesgo
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'estatus_int')) {
                $table->unsignedTinyInteger('estatus_int')->default(1)->after('Estatus');
                $table->index('estatus_int');
            }
        });

        // 2) Migrar datos: si Estatus trae '1'/'2' en varchar
        DB::statement("
            UPDATE empleados
            SET estatus_int =
                CASE
                    WHEN Estatus IS NULL OR Estatus = '' THEN 1
                    WHEN Estatus = '1' THEN 1
                    WHEN Estatus = '2' THEN 2
                    ELSE 1
                END
        ");

        // 3) (Opcional recomendado) Renombrar: dejar Estatus como backup y usar estatus_int como estatus
        // Para no arriesgar, lo dejamos así hoy.
        // Más adelante puedes:
        // - dropear Estatus
        // - renombrar estatus_int -> estatus
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (Schema::hasColumn('empleados', 'estatus_int')) {
                $table->dropColumn('estatus_int');
            }
        });
    }
};
