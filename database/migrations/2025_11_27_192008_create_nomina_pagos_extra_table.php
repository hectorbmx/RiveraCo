<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_pagos_extra', function (Blueprint $table) {
            $table->id();

            // Empleado (FK a empleados.id_Empleado)
            $table->integer('empleado_id');
            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnDelete();

            // Opcional: asociar a una obra
            $table->foreignId('obra_id')
                ->nullable()
                ->constrained('obras')
                ->nullOnDelete();

            // Tipo de pago extraordinario
            // Ej: aguinaldo, prima_vacacional, ptu, bono, otro
            $table->string('tipo', 30);

            // Año fiscal o ejercicio al que corresponde
            $table->year('anio')->nullable();

            // Texto libre del periodo: "Aguinaldo 2024", "PTU 2025", etc.
            $table->string('concepto', 150)->nullable();

            // Monto pagado
            $table->decimal('monto', 12, 2);

            // Fecha en que se pagó
            $table->date('fecha_pago')->nullable();

            // Referencias externas (folio CFDI, número de póliza, etc.)
            $table->string('folio', 50)->nullable();
            $table->string('referencia_externa', 100)->nullable();

            // Notas internas
            $table->string('notas', 255)->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index(['empleado_id', 'tipo']);
            $table->index('anio');
            $table->index('fecha_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_pagos_extra');
    }
};
