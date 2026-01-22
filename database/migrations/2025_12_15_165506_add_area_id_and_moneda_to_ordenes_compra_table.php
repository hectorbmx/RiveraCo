<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {

            // AREA_ID (FK a areas)
            if (!Schema::hasColumn('ordenes_compra', 'area_id')) {
                $table->unsignedBigInteger('area_id')
                    ->nullable()
                    ->after('obra_id');

                $table->index('area_id', 'ordenes_compra_area_id_index');

                $table->foreign('area_id', 'ordenes_compra_area_id_foreign')
                    ->references('id')
                    ->on('areas')
                    ->nullOnDelete();
            }

            // MONEDA
            if (!Schema::hasColumn('ordenes_compra', 'moneda')) {
                $table->string('moneda', 10)
                    ->default('MXN')
                    ->after('tipo_cambio');

                $table->index('moneda', 'ordenes_compra_moneda_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {

            // MONEDA
            if (Schema::hasColumn('ordenes_compra', 'moneda')) {
                $table->dropIndex('ordenes_compra_moneda_index');
                $table->dropColumn('moneda');
            }

            // AREA_ID
            if (Schema::hasColumn('ordenes_compra', 'area_id')) {
                $table->dropForeign('ordenes_compra_area_id_foreign');
                $table->dropIndex('ordenes_compra_area_id_index');
                $table->dropColumn('area_id');
            }
        });
    }
};
