<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'gastos_sin_factura')) {
                $afterColumn = Schema::hasColumn('ordenes_compra', 'es_caja_chica') ? 'es_caja_chica' : 'area_id';
                $table->boolean('gastos_sin_factura')
                    ->default(false)
                    ->after($afterColumn)
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'gastos_sin_factura')) {
                $table->dropColumn('gastos_sin_factura');
            }
        });
    }
};
