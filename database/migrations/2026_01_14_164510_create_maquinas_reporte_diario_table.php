<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquinas_reporte_diario', function (Blueprint $table) {
            $table->id();

            $table->date('fecha');

            $table->unsignedBigInteger('obra_id');
            $table->unsignedBigInteger('maquina_id');

            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Un registro por día por obra por máquina
            $table->unique(['fecha', 'obra_id', 'maquina_id'], 'uq_mrd_fecha_obra_maquina');

            // FKs (ajusta nombres de tablas si aplica)
            $table->foreign('obra_id')->references('id')->on('obras')->onDelete('cascade');
            $table->foreign('maquina_id')->references('id')->on('maquinas')->onDelete('cascade');

            // created_by -> users.id (si manejas users)
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            // Índices útiles para consultas
            $table->index(['fecha'], 'idx_mrd_fecha');
            $table->index(['obra_id'], 'idx_mrd_obra');
            $table->index(['maquina_id'], 'idx_mrd_maquina');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquinas_reporte_diario');
    }
};
