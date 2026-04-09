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
       Schema::create('presupuesto_pilas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->onDelete('cascade');
            $table->string('concepto');           // Columna B
            $table->string('unidad')->nullable(); // Columna C
            $table->decimal('cantidad', 15, 2);   // Columna D
            $table->decimal('costo', 15, 2);      // Columna E
            $table->decimal('total', 15, 2);      // Columna F (Cant * Costo)
            $table->decimal('optimista', 15, 2)->nullable(); // Columna G
            $table->decimal('pesimista', 15, 2)->nullable();  // Columna H
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_pilas');
    }
};
