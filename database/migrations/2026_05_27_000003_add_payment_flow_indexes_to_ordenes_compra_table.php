<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->index('estado', 'ordenes_compra_estado_index');
            $table->index('fecha', 'ordenes_compra_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('ordenes_compra_estado_index');
            $table->dropIndex('ordenes_compra_fecha_index');
        });
    }
};
