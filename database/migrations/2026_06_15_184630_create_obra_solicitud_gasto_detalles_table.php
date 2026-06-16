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
        Schema::create('obra_solicitud_gasto_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_solicitud_gasto_id')
                  ->constrained('obra_solicitud_gastos')
                  ->onDelete('cascade')
                  ->name('fk_solicitud_detalle_parent');

            $table->foreignId('planeacion_gasto_id')
                  ->constrained('obra_planeacion_gastos')
                  ->name('fk_solicitud_detalle_planeacion');

            $table->decimal('monto_solicitado', 15, 2);
            $table->string('concepto_manual')->nullable(); // Por si quieren pedir algo extra no planeado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_solicitud_gasto_detalles');
    }
};
