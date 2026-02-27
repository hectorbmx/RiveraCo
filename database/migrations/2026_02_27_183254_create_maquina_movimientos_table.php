<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquina_movimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maquina_id')
                ->constrained('maquinas')
                ->cascadeOnDelete();

            // Opcionales: para ligar el evento a obra y/o a la asignación (pivot)
            $table->foreignId('obra_id')
                ->nullable()
                ->constrained('obras')
                ->nullOnDelete();

            $table->foreignId('obra_maquina_id')
                ->nullable()
                ->constrained('obra_maquina')
                ->nullOnDelete();

            // Clasificación del evento
            $table->enum('tipo', [
                'asignacion',
                'desasignacion',
                'cambio_ubicacion',
                'cambio_estado',
                'reparacion_inicia',
                'reparacion_termina',
            ])->default('cambio_ubicacion');

            // Ubicación
            $table->enum('ubicacion_anterior', [
                'en_obra',
                'en_camino',
                'en_reparacion',
                'en_patio',
            ])->nullable();

            $table->enum('ubicacion_nueva', [
                'en_obra',
                'en_camino',
                'en_reparacion',
                'en_patio',
            ])->nullable();

            // Estado/condición (mismo vocabulario que maquinas.estado)
            $table->enum('estado_anterior', [
                'operativa',
                'fuera_servicio',
                'baja_definitiva',
            ])->nullable();

            $table->enum('estado_nuevo', [
                'operativa',
                'fuera_servicio',
                'baja_definitiva',
            ])->nullable();

            $table->string('motivo', 190)->nullable();
            $table->text('notas')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Fecha real del evento (no dependemos de created_at)
            $table->dateTime('fecha_evento')->useCurrent();

            $table->timestamps();

            // Índices para consultas típicas
            $table->index(['maquina_id', 'fecha_evento']);
            $table->index(['obra_id', 'fecha_evento']);
            $table->index(['obra_maquina_id', 'fecha_evento']);
            $table->index(['tipo', 'fecha_evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquina_movimientos');
    }
};