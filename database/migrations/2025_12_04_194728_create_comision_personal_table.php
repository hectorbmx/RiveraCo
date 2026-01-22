<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comision_personal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_id')
                  ->constrained('comisiones')
                  ->cascadeOnDelete();

            // Empleado asignado a la obra
            $table->foreignId('obra_empleado_id')
                  ->constrained('obra_empleado')
                  ->cascadeOnDelete();

            // Máquina que utilizó ese empleado en esa comisión (si aplica)
            $table->foreignId('obra_maquina_id')
                  ->nullable()
                  ->constrained('obra_maquina')
                  ->nullOnDelete();

            // Rol dentro del formato: OPERADOR, AYUDANTE OP, COLADOR, etc.
            $table->string('rol', 50)->nullable();

            // Checkbox "trabajó" para ese día
            $table->boolean('trabaja')->default(true);

            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            // Comida en minutos
            $table->unsignedSmallInteger('comida_min')->nullable();

            // Horas y tiempo extra (guardado ya calculado si quieres)
            $table->decimal('horas_laboradas', 5, 2)->nullable();
            $table->decimal('tiempo_extra', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['comision_id', 'rol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_personal');
    }
};
