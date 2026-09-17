<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_documento_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_compra_id')->nullable()->after('producto_id');
            $table->unsignedBigInteger('orden_compra_detalle_id')->nullable()->after('orden_compra_id');

            $table->index('orden_compra_id');
            $table->index('orden_compra_detalle_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_documento_detalles', function (Blueprint $table) {
            $table->dropIndex(['orden_compra_id']);
            $table->dropIndex(['orden_compra_detalle_id']);
            $table->dropColumn(['orden_compra_id', 'orden_compra_detalle_id']);
        });
    }
};
