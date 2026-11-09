<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->foreignId('compra_recepcion_directa_id')->nullable();
            $table->foreign('compra_recepcion_directa_id', 'inventario_mov_crd_fk')->references('id')->on('compra_recepciones_directas')->restrictOnDelete();
        });
        Schema::table('compra_recepciones_directas', function (Blueprint $table) {
            $table->unique('entrada_documento_id', 'crd_entrada_unique');
            $table->unique('salida_documento_id', 'crd_salida_unique');
        });
    }

    public function down(): void
    {
        Schema::table('compra_recepciones_directas', function (Blueprint $table) {
            $table->dropUnique('crd_entrada_unique');
            $table->dropUnique('crd_salida_unique');
        });
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropForeign('inventario_mov_crd_fk');
            $table->dropColumn('compra_recepcion_directa_id');
        });
    }
};
