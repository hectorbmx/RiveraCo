<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('area_horarios')) {
            return;
        }

        Schema::create('area_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')
                ->constrained('areas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('nombre')->default('Horario base');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->json('dias_laborables')->nullable();
            $table->unsignedSmallInteger('minutos_comida')->default(0);
            $table->unsignedSmallInteger('minutos_tolerancia')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['area_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_horarios');
    }
};