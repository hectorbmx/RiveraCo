<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguros_maquinas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maquina_id')
                ->constrained('maquinas')
                ->cascadeOnDelete();

            $table->string('aseguradora', 150);
            $table->string('numero_poliza', 80);

            $table->date('vigencia_inicio');
            $table->date('vigencia_fin');

            $table->string('cobertura', 190)->nullable();
            $table->decimal('suma_asegurada', 14, 2)->nullable();
            $table->decimal('deducible', 14, 2)->nullable();

            // PDF/imagen en storage
            $table->string('archivo_path', 255)->nullable();

            $table->text('notas')->nullable();

            $table->timestamps();

            // Índices típicos
            $table->index(['maquina_id', 'vigencia_fin']);
            $table->index(['vigencia_fin']);
            $table->unique(['maquina_id', 'numero_poliza']); // evita duplicados por máquina
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguros_maquinas');
    }
};