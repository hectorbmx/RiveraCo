<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_abastecimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mantenimiento_id')
                ->constrained('mantenimientos')
                ->cascadeOnDelete();
            $table->string('folio', 30)->nullable()->unique();
            $table->string('tipo', 20); // almacen | compra
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['mantenimiento_id', 'tipo', 'estado'], 'mant_abastecimientos_flujo_index');
        });

        Schema::create('mantenimiento_abastecimiento_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('abastecimiento_id')
                ->constrained('mantenimiento_abastecimientos')
                ->cascadeOnDelete();
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->nullOnDelete();
            $table->unsignedBigInteger('material_preventivo_id')->nullable();
            $table->string('producto_nombre_snapshot', 255);
            $table->string('producto_sku_snapshot', 100)->nullable();
            $table->string('unidad', 50)->nullable();
            $table->decimal('cantidad_requerida', 12, 3)->default(0);
            $table->decimal('cantidad_solicitada', 12, 3)->default(0);
            $table->decimal('cantidad_atendida', 12, 3)->default(0);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->index(['abastecimiento_id', 'estado'], 'mant_abastecimiento_detalles_estado_index');
            $table->index('material_preventivo_id', 'mant_abastecimiento_detalles_material_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_abastecimiento_detalles');
        Schema::dropIfExists('mantenimiento_abastecimientos');
    }
};
