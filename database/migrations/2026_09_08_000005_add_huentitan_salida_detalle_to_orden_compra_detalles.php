<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('orden_compra_detalles', 'huentitan_salida_detalle_id')) {
                $table->foreignId('huentitan_salida_detalle_id')
                    ->nullable()
                    ->after('huentitan_orden_fabricacion_material_id')
                    ->constrained('huentitan_salida_detalles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('orden_compra_detalles', 'huentitan_salida_detalle_id')) {
                $table->dropConstrainedForeignId('huentitan_salida_detalle_id');
            }
        });
    }
};
