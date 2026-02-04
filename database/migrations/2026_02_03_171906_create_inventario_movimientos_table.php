<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('almacen_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('documento_id');

            // Fecha efectiva del movimiento
            $table->dateTime('fecha');

            // Dirección del movimiento
            $table->enum('tipo_movimiento', ['in','out']);

            // Cantidad del movimiento (siempre positiva)
            $table->decimal('cantidad', 14, 3);

            // Costo aplicado en el movimiento
            // - Entradas: costo capturado
            // - Salidas: costo promedio ponderado al momento
            $table->decimal('costo_unitario', 14, 4);

            // Snapshot del saldo después del movimiento (opcional pero MUY útil)
            $table->decimal('saldo_cantidad', 14, 3)->nullable();

            // Centro de costo / trazabilidad
            $table->unsignedBigInteger('obra_id')->nullable();
            $table->unsignedBigInteger('residente_id')->nullable();

            // Auditoría
            $table->unsignedBigInteger('creado_por')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['producto_id', 'fecha']);
            $table->index('almacen_id');
            $table->index('documento_id');
            $table->index('obra_id');
            $table->index('residente_id');

            // FKs (comentadas por seguridad)
            // $table->foreign('almacen_id')->references('id')->on('almacenes');
            // $table->foreign('producto_id')->references('id')->on('productos');
            // $table->foreign('documento_id')->references('id')->on('inventario_documentos')->cascadeOnDelete();
            // $table->foreign('obra_id')->references('id')->on('obras')->nullOnDelete();
            // $table->foreign('residente_id')->references('id')->on('empleados')->nullOnDelete();
            // $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
    }
};
