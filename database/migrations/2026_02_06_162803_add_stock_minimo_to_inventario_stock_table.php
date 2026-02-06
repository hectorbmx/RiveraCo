<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('inventario_stock', function (Blueprint $table) {
        $table->decimal('stock_minimo', 12, 2)->nullable()->after('stock_actual');
    });
}

public function down(): void
{
    Schema::table('inventario_stock', function (Blueprint $table) {
        $table->dropColumn('stock_minimo');
    });
}
};
