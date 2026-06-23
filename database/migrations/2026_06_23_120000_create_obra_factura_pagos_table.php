<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_factura_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('factura_uuid', 80)->index();
            $table->string('factura_source', 30)->nullable();
            $table->decimal('monto', 15, 2);
            $table->date('fecha_pago');
            $table->foreignId('cuenta_banco_empresa_id')->nullable()->constrained('cuentas_banco_empresa')->nullOnDelete();
            $table->foreignId('metodo_pago_empresa_id')->nullable()->constrained('metodos_pago_empresa')->nullOnDelete();
            $table->string('referencia', 120)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('requiere_complemento_pago')->default(false);
            $table->foreignId('sat_factura_pago_id')->nullable()->constrained('sat_factura_pagos')->nullOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registrado_at')->nullable();
            $table->timestamps();

            $table->index(['obra_id', 'factura_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_factura_pagos');
    }
};
