<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_subpartidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_partida_id')->constrained('presupuesto_partidas')->cascadeOnDelete();
            $table->text('concepto');
            $table->string('unidad', 32);
            $table->decimal('cantidad', 15, 2)->unsigned();
            $table->decimal('precio_unitario', 15, 2)->unsigned();
            $table->unsignedInteger('orden');
            $table->timestamps();

            $table->index(['presupuesto_partida_id', 'orden'], 'presupuesto_subpartidas_orden_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_subpartidas');
    }
};
