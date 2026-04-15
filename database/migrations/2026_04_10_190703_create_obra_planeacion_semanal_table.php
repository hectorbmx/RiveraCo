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
      Schema::create('obra_planeacion_semanal', function (Blueprint $table) {
    $table->id();
    // $table->foreignId('planeacion_gasto_id')->constrained('planeacion_gastos')->onDelete('cascade');
    $table->foreignId('planeacion_gasto_id')
      ->constrained('obra_planeacion_gastos')
      ->onDelete('cascade');
    $table->integer('numero_semana'); // 1, 2, 3...
    $table->decimal('monto_programado', 15, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_planeacion_semanal');
    }
};
