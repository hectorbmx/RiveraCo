<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventario_stock', function (Blueprint $table) {
            $table->decimal('valor_total', 14, 4)->default(0)->after('stock_reservado');
            $table->decimal('costo_promedio', 14, 4)->default(0)->after('valor_total');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_stock', function (Blueprint $table) {
            $table->dropColumn(['valor_total','costo_promedio']);
        });
    }
};
