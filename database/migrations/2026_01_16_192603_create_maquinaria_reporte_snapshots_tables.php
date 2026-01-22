<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Cabecera por fecha (un snapshot global por día)
        Schema::create('maquinaria_reporte_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique(); // un snapshot por fecha
            $table->string('estado', 20)->default('abierto'); // abierto|cerrado (opcional)
            $table->unsignedInteger('total_maquinas')->default(0); // opcional, para métricas rápidas

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('fecha');
        });

        // Detalle por máquina (una fila por máquina en esa fecha)
        Schema::create('maquinaria_reporte_snapshot_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('snapshot_id');
            $table->unsignedBigInteger('maquina_id');

            // Asignación (nullable si está en almacén / sin obra)
            $table->unsignedBigInteger('obra_id')->nullable();
            $table->unsignedBigInteger('obra_maquina_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();

            // Nombres "freeze" para histórico (evitar que cambien por renombres)
            $table->string('obra_nombre')->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->string('residente_nombre')->nullable();

            // KPIs de obra (freeze)
            $table->unsignedInteger('pilas_programadas')->default(0);
            $table->unsignedInteger('pilas_ejecutadas')->default(0);
            $table->unsignedTinyInteger('avance_global_pct')->default(0);

            // Horómetros / horas (freeze)
            $table->decimal('horometro_inicio_obra', 12, 2)->nullable();
            $table->decimal('horometro_actual', 12, 2)->nullable();
            $table->decimal('horas_trabajadas', 12, 2)->default(0);

            // Financiero (freeze)
            $table->decimal('total_obra', 14, 2)->default(0);
            $table->decimal('monto_cobrado', 14, 2)->default(0);
            $table->unsignedTinyInteger('pago_pct')->default(0);

            // Datos de máquina (freeze)
            $table->string('maquina_nombre')->nullable();
            $table->string('maquina_codigo')->nullable();
            $table->string('placas')->nullable();
            $table->string('color')->nullable();
            $table->decimal('horometro_base', 12, 2)->nullable();
            $table->string('maquina_estado')->nullable(); // operativa, etc.

            // Equipo (freeze) - lista (JSON) para no depender de joins al visualizar histórico
            $table->json('equipo')->nullable();

            // Observaciones
            $table->text('observaciones_comisiones')->nullable(); // concatenado automático (freeze)
            $table->text('observaciones_snapshot')->nullable();   // editable por usuario

            // Estado operativo futuro (mantenimiento)
            $table->string('estatus_flotilla', 30)->nullable(); // taller|reparacion|bloqueada|operativa|etc
            $table->text('motivo_estatus')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('snapshot_id')->references('id')->on('maquinaria_reporte_snapshots')->onDelete('cascade');

            // Una fila por máquina dentro de un snapshot
            $table->unique(['snapshot_id', 'maquina_id']);

            $table->index(['maquina_id']);
            $table->index(['obra_id']);
            $table->index(['cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquinaria_reporte_snapshot_items');
        Schema::dropIfExists('maquinaria_reporte_snapshots');
    }
};
