<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_factura_borradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->date('fecha');
            $table->string('forma_pago', 10)->nullable();
            $table->string('metodo_pago', 10);
            $table->string('uso_cfdi', 10);
            $table->string('regimen_fiscal', 10)->nullable();
            $table->foreignId('sat_concepto_id')->nullable()->constrained('sat_conceptos')->nullOnDelete();
            $table->string('concepto_descripcion');
            $table->decimal('cantidad', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('retenciones', 15, 2)->default(0);
            $table->decimal('descuentos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('estatus', 30)->default('pendiente_revision');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizado_at')->nullable();
            $table->foreignId('rechazado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rechazado_at')->nullable();
            $table->text('observaciones_revision')->nullable();
            $table->foreignId('sat_factura_id')->nullable()->constrained('sat_facturas')->nullOnDelete();
            $table->foreignId('sat_cfdi_id')->nullable()->constrained('sat_cfdis')->nullOnDelete();
            $table->timestamps();

            $table->index(['obra_id', 'estatus']);
            $table->index(['cliente_id', 'estatus']);
            $table->index(['creado_por', 'estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_factura_borradores');
    }
};
