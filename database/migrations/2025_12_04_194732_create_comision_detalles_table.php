<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comision_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_id')
                  ->constrained('comisiones')
                  ->cascadeOnDelete();

            // Opcional: si quieres asociar producción por máquina
            $table->foreignId('obra_maquina_id')
                  ->nullable()
                  ->constrained('obra_maquina')
                  ->nullOnDelete();

            $table->string('diametro', 50)->nullable();
            $table->integer('cantidad')->nullable();

            $table->decimal('profundidad', 8, 2)->nullable();
            $table->decimal('metros_sujetos_comision', 10, 2)->nullable();

            $table->decimal('kg_acero', 12, 2)->nullable();
            $table->decimal('vol_bentonita', 12, 2)->nullable();
            $table->decimal('vol_concreto', 12, 2)->nullable();

            $table->string('adicional', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_detalles');
    }
};
