<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposicion_caja_chica_subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')
                ->constrained('reposicion_caja_chica_categorias')
                ->cascadeOnDelete();
            $table->string('codigo', 80)->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['categoria_id', 'codigo']);
            $table->index(['categoria_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_caja_chica_subcategorias');
    }
};
