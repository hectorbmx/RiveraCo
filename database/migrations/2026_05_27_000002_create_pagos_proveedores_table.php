<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cuenta_banco_empresa_id')->nullable()->constrained('cuentas_banco_empresa')->nullOnDelete();
            $table->date('fecha_programada');
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto', 14, 2);
            $table->string('moneda', 10)->default('MXN');
            $table->string('metodo_pago', 50)->nullable();
            $table->string('referencia', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estatus', 30)->default('programado');
            $table->foreignId('programado_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizado_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizado_at')->nullable();
            $table->foreignId('pagado_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamps();

            $table->index(['fecha_programada', 'estatus']);
            $table->index(['orden_compra_id', 'estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_proveedores');
    }
};
