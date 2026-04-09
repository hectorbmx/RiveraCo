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
    Schema::create('presupuesto_detalles', function (Blueprint $table) {
        $table->id();
        // Relación con la tabla presupuestos
        $table->foreignId('presupuesto_id')->constrained('presupuestos')->onDelete('cascade');
        
        $table->string('partida')->nullable();      // Ej: "TRASLADO DE PERFORADORA"
        $table->text('concepto');                  // Columna A/B
        $table->string('unidad')->nullable();      // Columna C
        $table->decimal('cantidad', 15, 2);        // Columna D (Filtro > 0)
        $table->decimal('precio_unitario', 15, 2); // Columna E/F/G (según tu Excel)
        $table->decimal('importe', 15, 2);         // Cantidad * Precio
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_detalles');
    }
};
