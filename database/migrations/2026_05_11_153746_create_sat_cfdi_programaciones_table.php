<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sat_cfdi_programaciones', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELACION CFDI (OPCIONAL)
            |--------------------------------------------------------------------------
            */

            $table->foreignId('sat_cfdi_id')
                ->nullable()
                ->constrained('sat_cfdis')
                ->nullOnDelete();

            $table->char('cfdi_uuid', 36)->nullable();

            /*
            |--------------------------------------------------------------------------
            | ORIGEN
            |--------------------------------------------------------------------------
            */

            // cfdi | manual
            $table->string('origen', 30)->default('cfdi');

            /*
            |--------------------------------------------------------------------------
            | INFORMACION GENERAL
            |--------------------------------------------------------------------------
            */

            $table->string('area', 80)->nullable();

            $table->string('proveedor_nombre')->nullable();

            $table->string('proveedor_rfc', 20)->nullable();

            $table->text('concepto')->nullable();

            $table->date('fecha_gasto')->nullable();

            $table->date('fecha_programada');

            $table->decimal('monto_programado', 15, 2);

            $table->string('moneda', 10)->default('MXN');

            $table->decimal('tipo_cambio', 15, 6)->nullable();

            /*
            |--------------------------------------------------------------------------
            | FACTURA
            |--------------------------------------------------------------------------
            */

            $table->boolean('requiere_factura')->default(true);

            // pendiente | recibida | no_recibida | no_aplica
            $table->string('estatus_factura', 30)
                ->default('pendiente');

            /*
            |--------------------------------------------------------------------------
            | PAGO
            |--------------------------------------------------------------------------
            */

            $table->string('tipo_pago', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | FLUJO DE AUTORIZACION
            |--------------------------------------------------------------------------
            */

            /*
            borrador
            pendiente_revision_admin
            observada_admin
            rechazada_admin
            pendiente_aprobacion_ceo
            observada_ceo
            rechazada_ceo
            aprobada
            pagada
            cancelada
            */

            $table->string('estatus', 50)
                ->default('pendiente_revision_admin');

            /*
            |--------------------------------------------------------------------------
            | SOLICITUD AREA
            |--------------------------------------------------------------------------
            */

            $table->foreignId('solicitado_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('solicitado_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | REVISION ADMIN
            |--------------------------------------------------------------------------
            */

            $table->foreignId('revisado_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('revisado_at')->nullable();

            $table->text('comentario_revision')->nullable();

            /*
            |--------------------------------------------------------------------------
            | APROBACION CEO
            |--------------------------------------------------------------------------
            */

            $table->foreignId('aprobado_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('aprobado_at')->nullable();

            $table->text('comentario_aprobacion')->nullable();

            /*
            |--------------------------------------------------------------------------
            | PAGO REAL RELACIONADO
            |--------------------------------------------------------------------------
            */

            $table->foreignId('sat_cfdi_pago_id')
                ->nullable()
                ->constrained('sat_cfdi_pagos')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | EXTRA
            |--------------------------------------------------------------------------
            */

            $table->text('observaciones')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index('fecha_programada');

            $table->index('estatus');

            $table->index('estatus_factura');

            $table->index('origen');

            $table->index('proveedor_rfc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_cfdi_programaciones');
    }
};