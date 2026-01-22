<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comision_perforaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_id')
                  ->constrained('comisiones')
                  ->cascadeOnDelete();

            $table->time('hora_inicio')->nullable();
            $table->time('hora_termino')->nullable();

            $table->string('informacion_pila', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_perforaciones');
    }
};
