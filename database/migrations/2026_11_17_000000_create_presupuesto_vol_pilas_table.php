<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->string('tipo', 80);
            $table->decimal('diametro', 8, 3);
            $table->unsignedInteger('piezas');
            $table->decimal('pilas_por_jornal', 8, 3);
            $table->decimal('longitud_l0', 8, 3);
            $table->decimal('longitud_l1', 8, 3)->default(0);
            $table->decimal('longitud_l2', 8, 3)->default(0);
            $table->decimal('acero_kg_por_pila', 10, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_vol_pilas');
    }
};
