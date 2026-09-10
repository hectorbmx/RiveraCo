<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_entrada_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('huentitan_entrada_detalles', 'huentitan_salida_detalle_id')) {
                $table->foreignId('huentitan_salida_detalle_id')
                    ->nullable()
                    ->after('orden_compra_detalle_id')
                    ->constrained('huentitan_salida_detalles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_entrada_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('huentitan_entrada_detalles', 'huentitan_salida_detalle_id')) {
                $table->dropConstrainedForeignId('huentitan_salida_detalle_id');
            }
        });
    }
};
