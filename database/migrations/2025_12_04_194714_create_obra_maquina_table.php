<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_maquina', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obra_id')
                  ->constrained('obras')
                  ->cascadeOnDelete();

            $table->foreignId('maquina_id')
                  ->constrained('maquinas')
                  ->cascadeOnDelete();

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->enum('estado', ['activa', 'finalizada'])
                  ->default('activa');

            $table->text('notas')->nullable();

            // Quién hizo la asignación (opcional)
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // Índices útiles
            $table->index(['maquina_id', 'estado']);
            $table->index(['obra_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_maquina');
    }
};
