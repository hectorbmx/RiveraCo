<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_salida_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('huentitan_salida_detalles', 'stock_disponible_snapshot')) {
                $table->decimal('stock_disponible_snapshot', 14, 3)->default(0)->after('cantidad_salida');
            }
            if (! Schema::hasColumn('huentitan_salida_detalles', 'cantidad_faltante')) {
                $table->decimal('cantidad_faltante', 14, 3)->default(0)->after('stock_disponible_snapshot');
            }
            if (! Schema::hasColumn('huentitan_salida_detalles', 'requiere_compra')) {
                $table->boolean('requiere_compra')->default(false)->after('cantidad_faltante');
            }
            if (! Schema::hasColumn('huentitan_salida_detalles', 'cantidad_sugerida_compra')) {
                $table->decimal('cantidad_sugerida_compra', 14, 3)->default(0)->after('requiere_compra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_salida_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('huentitan_salida_detalles', 'cantidad_sugerida_compra')) {
                $table->dropColumn('cantidad_sugerida_compra');
            }
            if (Schema::hasColumn('huentitan_salida_detalles', 'requiere_compra')) {
                $table->dropColumn('requiere_compra');
            }
            if (Schema::hasColumn('huentitan_salida_detalles', 'cantidad_faltante')) {
                $table->dropColumn('cantidad_faltante');
            }
            if (Schema::hasColumn('huentitan_salida_detalles', 'stock_disponible_snapshot')) {
                $table->dropColumn('stock_disponible_snapshot');
            }
        });
    }
};
