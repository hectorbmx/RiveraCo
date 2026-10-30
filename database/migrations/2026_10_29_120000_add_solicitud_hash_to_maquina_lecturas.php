<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['obra_maquina_registros', 'maquina_horometro_ajustes'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('solicitud_hash', 64)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        foreach (['obra_maquina_registros', 'maquina_horometro_ajustes'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropUnique(['solicitud_hash']);
                $table->dropColumn('solicitud_hash');
            });
        }
    }
};
