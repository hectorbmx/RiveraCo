<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_cfdi_pagos', function (Blueprint $table) {
            $table->id();

            // Relación principal con CFDI
            $table->foreignId('sat_cfdi_id')
                ->constrained('sat_cfdis')
                ->cascadeOnDelete();

            // Snapshot para búsquedas/auditoría
            $table->uuid('cfdi_uuid')->nullable()->index();

            // Datos del pago
            $table->date('fecha_pago');
            $table->decimal('monto', 15, 2);
            $table->string('moneda', 10)->default('MXN');
            $table->decimal('tipo_cambio', 15, 6)->nullable();

            // Método interno de captura
            $table->string('metodo_pago', 50)->nullable();
            $table->string('referencia')->nullable();

            // Campos abiertos según tipo de pago
            $table->string('folio_transferencia')->nullable();
            $table->string('numero_cheque')->nullable();

            $table->string('banco_origen')->nullable();
            $table->string('banco_destino')->nullable();
            $table->string('cuenta_origen')->nullable();
            $table->string('cuenta_destino')->nullable();

            // Evidencia y notas
            $table->text('observaciones')->nullable();
            $table->string('comprobante_path')->nullable();

            // Estado del pago
            $table->string('estatus', 30)->default('activo')->index();

            // Auditoría básica
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Cancelación
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['sat_cfdi_id', 'estatus']);
            $table->index(['fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_cfdi_pagos');
    }
};