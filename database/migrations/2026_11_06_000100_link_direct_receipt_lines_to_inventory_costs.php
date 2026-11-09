<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['inventario_movimientos', 'inventario_documento_detalles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('compra_recepcion_directa_detalle_id')->nullable();
                $table->foreign('compra_recepcion_directa_detalle_id', $tableName === 'inventario_movimientos' ? 'im_crdd_fk' : 'idd_crdd_fk')
                    ->references('id')->on('compra_recepcion_directa_detalles')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['inventario_documento_detalles', 'inventario_movimientos'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign($tableName === 'inventario_movimientos' ? 'im_crdd_fk' : 'idd_crdd_fk');
                $table->dropColumn('compra_recepcion_directa_detalle_id');
            });
        }
    }
};
