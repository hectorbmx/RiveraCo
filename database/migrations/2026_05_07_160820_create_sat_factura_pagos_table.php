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
    Schema::create('sat_factura_pagos', function (Blueprint $table) {
        $table->id();

        $table->foreignId('sat_factura_id')
            ->constrained('sat_facturas')
            ->cascadeOnDelete();

        // PAC / CFDI Pago
        $table->string('facturapi_invoice_id')->nullable();
        $table->string('uuid')->nullable();

        // Datos del pago
        $table->dateTime('fecha_pago');
        $table->string('forma_pago', 5);
        $table->string('moneda', 5)->default('MXN');
        $table->decimal('tipo_cambio', 18, 6)->nullable();

        // Importes
        $table->decimal('monto', 18, 2);
        $table->decimal('saldo_anterior', 18, 2)->nullable();
        $table->decimal('saldo_insoluto', 18, 2)->nullable();
        $table->unsignedInteger('numero_parcialidad')->nullable();

        // Estado
        $table->string('estado')->default('registrado');

        // Archivos
        $table->string('xml_path')->nullable();
        $table->string('pdf_path')->nullable();

        // Debug
        $table->json('facturapi_response')->nullable();
        $table->text('error_message')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::dropIfExists('sat_factura_pagos');
}
};
