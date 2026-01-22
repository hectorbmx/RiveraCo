<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->cascadeOnDelete();

            $table->foreignId('obra_id')
                  ->nullable()
                  ->constrained('obras')
                  ->nullOnDelete();

            $table->enum('tipo', ['programado', 'emergencia'])
                  ->default('programado');

            $table->string('categoria_mantenimiento', 100)->nullable();
            $table->text('descripcion')->nullable();

            $table->integer('km_actuales')->nullable();
            $table->integer('km_proximo_servicio')->nullable();

            $table->dateTime('fecha_programada')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();

            $table->enum('estatus', ['pendiente', 'en_proceso', 'completado', 'cancelado'])
                  ->default('pendiente');

            // MECÁNICO: empleados.id_Empleado (INT)
            $table->integer('mecanico_id')->nullable();

            $table->foreign('mecanico_id')
                  ->references('id_Empleado')
                  ->on('empleados')
                  ->onDelete('set null');

            $table->decimal('costo_mano_obra', 12, 2)->default(0);
            $table->decimal('costo_refacciones', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);

            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
