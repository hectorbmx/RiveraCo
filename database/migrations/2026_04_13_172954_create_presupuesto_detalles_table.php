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
    // Schema::create('presupuesto_detalles', function (Blueprint $table) {
    //     $table->id();
    //     $table->foreignId('presupuesto_id')->constrained('presupuestos')->onDelete('cascade');
    //     $table->string('partida')->nullable();
    //     $table->text('concepto');
    //     $table->string('unidad')->nullable();
    //     $table->double('cantidad', 15, 2)->default(0);
    //     $table->double('precio_unitario', 15, 2)->default(0);
    //     $table->double('importe', 15, 2)->default(0);
    //     $table->double('importe_optimista', 15, 2)->nullable();
    //     $table->double('importe_pesimista', 15, 2)->nullable();
    //     $table->timestamps();
    // });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('presupuesto_detalles');
    }
};
