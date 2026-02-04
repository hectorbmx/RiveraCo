<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventario_documento_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('producto_id');

            // Cantidades decimales (litros, kg, metros, etc.)
            $table->decimal('cantidad', 14, 3);

            // Costo unitario:
            // - obligatorio en entradas / inicial / ajustes
            // - nullable en salidas (se calculará por promedio ponderado)
            $table->decimal('costo_unitario', 14, 4)->nullable();

            // Campo libre por renglón
            $table->string('notas', 150)->nullable();

            $table->timestamps();

            // Índices
            $table->index('documento_id');
            $table->index('producto_id');

            // FKs (comentadas por seguridad)
            // $table->foreign('documento_id')->references('id')->on('inventario_documentos')->cascadeOnDelete();
            // $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_documento_detalles');
    }
};
