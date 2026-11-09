<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->string('modalidad_entrega', 20)->default('almacen');
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['almacen_destino_id']);
            $table->dropColumn(['modalidad_entrega', 'almacen_destino_id']);
        });
    }
};
