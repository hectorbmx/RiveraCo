<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::table('obra_maquina', function (Blueprint $table) {
            $table->decimal('horometro_inicio', 10, 2)->nullable()->after('fecha_inicio');
            $table->decimal('horometro_fin', 10, 2)->nullable()->after('fecha_fin');

            // opcional: índices si vas a consultar por rangos (no obligatorio aún)
            // $table->index(['obra_id', 'maquina_id']);
        });
    }

    public function down(): void
    {
        Schema::table('obra_maquina', function (Blueprint $table) {
            $table->dropColumn(['horometro_inicio', 'horometro_fin']);
        });
    }
};
