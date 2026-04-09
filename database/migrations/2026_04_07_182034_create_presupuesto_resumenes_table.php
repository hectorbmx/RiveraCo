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
        Schema::create('presupuesto_resumenes', function (Blueprint $table) {
            $table->id();
            // Relación con la cabecera
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->onDelete('cascade');
            
            // Campos de datos
            $table->string('partida');           // Ej: "COLOCACION DE ACERO Y CONCRETO"
            $table->string('concepto');          // Ej: "Colocación de tubería"
            $table->string('unidad')->nullable(); 
            $table->decimal('cantidad', 15, 2)->default(0);
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('importe', 15, 2)->default(0); // Columna F del Excel
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_resumenes');
    }
};
