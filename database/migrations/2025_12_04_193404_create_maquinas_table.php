<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquinas', function (Blueprint $table) {
            $table->id();

            // Código corto: BG-25C, RETRO, etc.
            $table->string('codigo', 50)->unique();

            // Nombre descriptivo opcional
            $table->string('nombre', 150)->nullable();

            // Para clasificación y futuro mantenimiento
            $table->string('tipo', 50)->nullable();          // perforadora, retro, grua, etc.
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();

            // Estado general de la máquina (no de asignación a obra)
            $table->enum('estado', ['operativa', 'fuera_servicio', 'baja_definitiva'])
                  ->default('operativa');

            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquinas');
    }
};
