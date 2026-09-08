<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            $table->timestamp('calculada_at')->nullable()->after('creado_por');
            $table->foreignId('calculada_por')->nullable()->after('calculada_at');
            $table->foreign('calculada_por', 'hof_calculada_por_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            $table->decimal('stock_actual_calculado', 14, 3)->default(0)->after('costo_total_estimado');
            $table->decimal('stock_reservado_calculado', 14, 3)->default(0)->after('stock_actual_calculado');
            $table->decimal('stock_disponible_calculado', 14, 3)->default(0)->after('stock_reservado_calculado');
            $table->decimal('faltante_calculado', 14, 3)->default(0)->after('stock_disponible_calculado');
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            $table->dropColumn([
                'stock_actual_calculado',
                'stock_reservado_calculado',
                'stock_disponible_calculado',
                'faltante_calculado',
            ]);
        });

        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            $table->dropForeign('hof_calculada_por_fk');
            $table->dropColumn(['calculada_at', 'calculada_por']);
        });
    }
};
