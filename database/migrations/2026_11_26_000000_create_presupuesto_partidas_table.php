<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedInteger('orden');
            $table->timestamps();

            $table->index(['presupuesto_id', 'orden'], 'presupuesto_partidas_orden_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_partidas');
    }
};
