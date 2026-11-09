<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->foreignId('obra_requisicion_detalle_id')->nullable()
                ->constrained('obra_requisicion_detalles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('obra_requisicion_detalle_id');
        });
    }
};
