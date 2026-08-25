<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_asistencia_semanal_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained('obra_asistencia_semanal_reportes')->cascadeOnDelete();
            $table->unsignedBigInteger('empleado_id');
            $table->date('fecha');
            $table->boolean('planeado_asistir')->default(false);
            $table->string('estado_admin', 30)->default('planeado');
            $table->string('estado_campo', 30)->default('sin_evidencia');
            $table->foreignId('obra_asistencia_entrada_id')->nullable();
            $table->foreignId('obra_asistencia_salida_id')->nullable();
            $table->string('excepcion_tipo', 50)->nullable();
            $table->text('excepcion_motivo')->nullable();
            $table->foreignId('autorizado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('ajuste_nomina_id')->nullable();
            $table->timestamps();

            $table->unique(['reporte_id', 'empleado_id', 'fecha'], 'obra_asist_sem_det_unique');
            $table->index(['empleado_id', 'fecha']);
            $table->foreign('obra_asistencia_entrada_id', 'oas_det_entrada_fk')
                ->references('id')
                ->on('obras_asistencias')
                ->nullOnDelete();
            $table->foreign('obra_asistencia_salida_id', 'oas_det_salida_fk')
                ->references('id')
                ->on('obras_asistencias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_asistencia_semanal_detalles');
    }
};

