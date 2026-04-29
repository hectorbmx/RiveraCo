<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreignId('planeacion_gasto_id')
                  ->nullable()
                  ->after('obra_id')
                  ->constrained('obra_planeacion_gastos')
                  ->onDelete('set null');
        });
    }
 
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['planeacion_gasto_id']);
            $table->dropColumn('planeacion_gasto_id');
        });
    }
};
 