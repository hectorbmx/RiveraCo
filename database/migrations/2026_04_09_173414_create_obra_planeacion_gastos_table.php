<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::create('obra_planeacion_gastos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('obra_id')->constrained()->onDelete('cascade');
        
        // Referencias a los desgloses del presupuesto maestro
        $table->unsignedBigInteger('presupuesto_detalle_id')->nullable();
        $table->unsignedBigInteger('presupuesto_pila_id')->nullable();
        
        $table->integer('numero_semana');
        $table->decimal('monto_programado', 15, 2)->default(0);
        
        $table->timestamps();

        // Índices para velocidad de consulta
        $table->index(['obra_id', 'numero_semana']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_planeacion_gastos');
    }
};
