<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (!Schema::hasColumn('reposicion_caja_chica_gastos', 'vehiculo_id')) {
                $table->foreignId('vehiculo_id')
                    ->nullable()
                    ->after('maquina_id')
                    ->constrained('vehiculos')
                    ->nullOnDelete();

                $table->index(['vehiculo_id', 'fecha_gasto'], 'rcc_gastos_vehiculo_fecha_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (Schema::hasColumn('reposicion_caja_chica_gastos', 'vehiculo_id')) {
                $table->dropIndex('rcc_gastos_vehiculo_fecha_index');
                $table->dropConstrainedForeignId('vehiculo_id');
            }
        });
    }
};
