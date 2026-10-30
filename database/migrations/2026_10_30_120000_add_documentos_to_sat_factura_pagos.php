<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_factura_pagos', function (Blueprint $table) {
            $table->uuid('solicitud_id')->nullable()->unique();
            $table->string('solicitud_hash', 64)->nullable();
            $table->json('facturapi_payload')->nullable();
        });
        Schema::create('sat_factura_pago_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sat_factura_pago_id')->constrained('sat_factura_pagos')->cascadeOnDelete();
            $table->foreignId('sat_factura_id')->constrained('sat_facturas')->restrictOnDelete();
            $table->decimal('importe_aplicado', 18, 2);
            $table->decimal('saldo_anterior', 18, 2)->nullable();
            $table->decimal('saldo_insoluto', 18, 2)->nullable();
            $table->unsignedInteger('numero_parcialidad')->nullable();
            $table->json('facturapi_resumen')->nullable();
            $table->timestamps();
            $table->unique(['sat_factura_pago_id', 'sat_factura_id'], 'sat_pago_documento_unique');
        });

        // Los REP anteriores conservan importes, saldos, parcialidades y estado.
        DB::table('sat_factura_pagos')->orderBy('id')->chunkById(250, function ($pagos) {
            DB::table('sat_factura_pago_documentos')->insert($pagos->map(fn ($pago) => [
                'sat_factura_pago_id' => $pago->id, 'sat_factura_id' => $pago->sat_factura_id,
                'importe_aplicado' => $pago->monto, 'saldo_anterior' => $pago->saldo_anterior,
                'saldo_insoluto' => $pago->saldo_insoluto, 'numero_parcialidad' => $pago->numero_parcialidad,
                'created_at' => $pago->created_at, 'updated_at' => $pago->updated_at,
            ])->all());
        });
    }

    public function down(): void
    {
        if (DB::table('sat_factura_pagos')->whereNotNull('solicitud_id')
            ->whereIn('estado', ['en_proceso', 'pendiente_confirmacion', 'cancelacion_pendiente'])->exists()) {
            throw new RuntimeException('Hay operaciones fiscales pendientes de confirmar. No se puede retirar su información de recuperación.');
        }
        // Evitar perder el desglose de comprobantes fiscales agrupados al revertir.
        if (Schema::hasTable('sat_factura_pago_documentos') && DB::table('sat_factura_pago_documentos')
            ->select('sat_factura_pago_id')->groupBy('sat_factura_pago_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Existen complementos con varias facturas. Respalda su desglose antes de revertir esta migración.');
        }
        Schema::dropIfExists('sat_factura_pago_documentos');
        Schema::table('sat_factura_pagos', function (Blueprint $table) {
            $table->dropUnique(['solicitud_id']);
            $table->dropColumn(['solicitud_id', 'solicitud_hash', 'facturapi_payload']);
        });
    }
};
