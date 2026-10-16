<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquina_horometro_ajustes', function (Blueprint $table) {
            $table->date('fecha_registro')->nullable()->after('obra_maquina_id');
            $table->index(['maquina_id', 'fecha_registro']);
        });
    }

    public function down(): void
    {
        Schema::table('maquina_horometro_ajustes', function (Blueprint $table) {
            $table->dropIndex(['maquina_id', 'fecha_registro']);
            $table->dropColumn('fecha_registro');
        });
    }
};
