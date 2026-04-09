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
    Schema::create('presupuestos', function (Blueprint $table) {
        $table->id();
        $table->string('codigo_proyecto')->unique(); // Ej: PIL-2024-001
        $table->string('nombre_cliente');
        $table->string('descripcion')->nullable();
        
        // Totales (estos se pueden calcular en el Excel y mandar el resumen)
        $table->decimal('total_costo_directo', 15, 2)->default(0);
        $table->decimal('total_presupuesto', 15, 2)->default(0);
        
        // Metadatos del flujo
        $table->string('estatus')->default('borrador'); // borrador, enviado, aceptado, liquidacion
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_presupuestos');
    }
};
