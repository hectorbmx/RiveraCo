<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 20)->unique();      // ML, M3, KG, PZA, H
            $table->string('nombre', 100);              // Metro lineal, Metro cúbico...
            $table->string('simbolo', 20)->nullable();  // ml, m³, kg, pza, h
            $table->string('tipo', 50)->nullable();     // longitud, volumen, tiempo, peso, cantidad (opcional)
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uoms');
    }
};
