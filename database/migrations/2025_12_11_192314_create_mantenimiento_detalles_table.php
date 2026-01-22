<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_detalles', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('mantenimiento_id')
                  ->constrained('mantenimientos')
                  ->cascadeOnDelete();

            $table->string('concepto', 150);
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);

            $table->string('tipo', 50)->nullable(); // mano_obra, refaccion, etc.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_detalles');
    }
};
