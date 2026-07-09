<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_recibo_comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recibo_id')->constrained('nomina_recibos')->cascadeOnDelete();
            $table->foreignId('corrida_id')->constrained('nomina_corridas')->cascadeOnDelete();
            $table->foreignId('comision_id')->constrained('comisiones')->cascadeOnDelete();
            $table->foreignId('comision_personal_id')->constrained('comision_personal')->cascadeOnDelete();
            $table->unsignedInteger('empleado_id');
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->date('fecha_comision')->nullable();
            $table->decimal('importe_comision', 12, 2)->default(0);
            $table->decimal('tiempo_extra', 8, 2)->default(0);
            $table->string('rol', 80)->nullable();
            $table->timestamps();

            $table->unique(['recibo_id', 'comision_personal_id'], 'nomina_recibo_comision_unique');
            $table->index(['corrida_id', 'empleado_id'], 'nomina_recibo_comisiones_corrida_empleado_idx');
            $table->index(['comision_id', 'comision_personal_id'], 'nomina_recibo_comisiones_comision_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_recibo_comisiones');
    }
};