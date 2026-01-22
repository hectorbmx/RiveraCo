<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_empleado', function (Blueprint $table) {
            $table->id();

            // Obra
            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnDelete();

            // Empleado (apunta a id_Empleado)
            $table->integer('empleado_id');
            $table->foreign('empleado_id')
                  ->references('id_Empleado')
                  ->on('empleados')
                  ->cascadeOnDelete();

            // Periodo de asignación
            $table->date('fecha_alta');          // desde cuándo trabaja en esta obra
            $table->date('fecha_baja')->nullable(); // se llena cuando se mueve o sale

            // Estado actual de la asignación
            $table->boolean('activo')->default(true);

            // Info específica en la obra
            $table->string('puesto_en_obra', 100)->nullable();
            $table->decimal('sueldo_en_obra', 10, 2)->nullable(); // por si difiere del base
            $table->text('notas')->nullable();

            $table->timestamps();

            // Si en el futuro usas MariaDB 10.4 no hay índices parciales,
            // la regla "solo una asignación activa" se controla en la lógica.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_empleado');
    }
};
