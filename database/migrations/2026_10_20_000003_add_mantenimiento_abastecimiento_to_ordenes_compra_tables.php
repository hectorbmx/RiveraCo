<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->unsignedBigInteger('mantenimiento_abastecimiento_id')->nullable()->after('obra_id');
            $table->index('mantenimiento_abastecimiento_id', 'ocs_mant_abast_idx');
        });

        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('mantenimiento_abastecimiento_detalle_id')->nullable()->after('orden_compra_id');
            $table->index('mantenimiento_abastecimiento_detalle_id', 'ocd_mant_abast_det_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropIndex('ocd_mant_abast_det_idx');
            $table->dropColumn('mantenimiento_abastecimiento_detalle_id');
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('ocs_mant_abast_idx');
            $table->dropColumn('mantenimiento_abastecimiento_id');
        });
    }
};
