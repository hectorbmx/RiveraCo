<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_recepcion_directa_detalles', function (Blueprint $table) {
            $table->decimal('factor_inventario', 18, 8)->default(1);
            $table->decimal('cantidad_inventario', 15, 3)->default(0);
            $table->string('unidad_inventario', 40)->nullable();
        });
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->decimal('importe_costo', 18, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventario_movimientos', fn (Blueprint $table) => $table->dropColumn('importe_costo'));
        Schema::table('compra_recepcion_directa_detalles', fn (Blueprint $table) => $table->dropColumn(['factor_inventario', 'cantidad_inventario', 'unidad_inventario']));
    }
};
