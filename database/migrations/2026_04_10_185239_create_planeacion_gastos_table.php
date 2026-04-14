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
    Schema::create('planeacion_gastos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('obra_id')->constrained()->onDelete('cascade');
        $table->string('partida');
        $table->string('concepto');
        $table->string('unidad')->nullable();
        $table->decimal('cantidad', 15, 2)->default(0);
        $table->decimal('precio_unitario', 15, 2)->default(0);
        $table->decimal('total', 15, 2)->default(0);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planeacion_gastos');
    }
};
