<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventario_stock', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('almacen_id');
            $table->unsignedBigInteger('producto_id');

            // Stock disponible actual
            $table->decimal('stock_actual', 14, 3)->default(0);

            // Reservado (no lo usamos en fase 1, pero queda listo)
            $table->decimal('stock_reservado', 14, 3)->default(0);

            $table->timestamps();

            // Un registro único por producto + almacén
            $table->unique(['almacen_id', 'producto_id']);

            // Índices
            $table->index('almacen_id');
            $table->index('producto_id');

            // FKs (comentadas)
            // $table->foreign('almacen_id')->references('id')->on('almacenes');
            // $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_stock');
    }
};
