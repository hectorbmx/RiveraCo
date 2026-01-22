<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_pilas', function (Blueprint $table) {
            $table->id();

            // Código corto de la pila (ej. "100 CM", "80 CM", etc.)
            $table->string('codigo')->unique();

            // Descripción más larga (ej. "Pila 100 cm estructural", etc.)
            $table->string('descripcion')->nullable();

            // Diámetro en centímetros, por si lo quieres usar en cálculos
            $table->unsignedInteger('diametro_cm')->nullable();

            // Activa / inactiva en catálogo
            $table->boolean('activa')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_pilas');
    }
};
