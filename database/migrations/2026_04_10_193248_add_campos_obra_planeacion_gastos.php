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
    // Agregamos lo que realmente necesitamos del Excel
    $table->string('partida')->after('obra_id')->nullable();
    $table->string('concepto')->after('partida')->nullable();
    $table->string('unidad')->after('concepto')->nullable();
    $table->decimal('cantidad', 15, 2)->default(0)->after('unidad');
    $table->decimal('precio_unitario', 15, 2)->default(0)->after('cantidad');
    
    // Mantenemos presupuesto_detalle_id y presupuesto_pila_id por si acaso, 
    // pero ahora serán opcionales (nullable).
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
