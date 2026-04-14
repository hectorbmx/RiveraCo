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
        //
        Schema::table('obra_planeacion_gastos', function (Blueprint $table) {
    // Agregamos la llave foránea hacia la tabla presupuestos
    // La ponemos después de obra_id para mantener el orden
    $table->foreignId('presupuesto_id')
          ->after('obra_id')
          ->nullable() 
          ->constrained('presupuestos')
          ->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
