<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            $table->boolean('es_para_maquina')
                ->default(false)
                ->after('almacen_id');

            $table->foreignId('maquina_id')
                ->nullable()
                ->after('es_para_maquina')
                ->constrained('maquinas')
                ->nullOnDelete();

            $table->index(['maquina_id', 'fecha_gasto'], 'rcc_gastos_maquina_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            $table->dropIndex('rcc_gastos_maquina_fecha_index');
            $table->dropConstrainedForeignId('maquina_id');
            $table->dropColumn('es_para_maquina');
        });
    }
};