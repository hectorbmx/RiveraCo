<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposicion_caja_chica_gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relacion_id')->nullable()->constrained('reposicion_caja_chica_relaciones')->nullOnDelete();
            $table->foreignId('categoria_id')->constrained('reposicion_caja_chica_categorias')->restrictOnDelete();
            $table->foreignId('subcategoria_id')->nullable()->constrained('reposicion_caja_chica_subcategorias')->nullOnDelete();
            $table->string('destino', 20)->default('obra');
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->date('fecha_gasto');
            $table->string('proveedor_nombre');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('proveedor_rfc', 20)->nullable();
            $table->string('concepto', 1000);
            $table->string('forma_pago', 50)->nullable();
            $table->decimal('importe_registrado', 14, 2);
            $table->decimal('importe_autorizado', 14, 2)->nullable();
            $table->string('estado_autorizacion', 40)->default('borrador');
            $table->text('motivo_sin_factura')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelto_at')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->text('observaciones_autorizacion')->nullable();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('solicitado_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('estado_autorizacion');
            $table->index('fecha_gasto');
            $table->index('destino');
            $table->index(['obra_id', 'fecha_gasto']);
            $table->index(['almacen_id', 'fecha_gasto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_caja_chica_gastos');
    }
};
