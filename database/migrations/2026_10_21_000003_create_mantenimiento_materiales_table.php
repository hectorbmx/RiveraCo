<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_materiales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('material_preventivo_id')->nullable()->constrained('empresa_servicio_preventivo_materiales')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('producto_nombre_snapshot', 255);
            $table->string('producto_sku_snapshot', 100)->nullable();
            $table->string('unidad', 30)->nullable();
            $table->decimal('cantidad_requerida', 12, 3)->default(0);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('incluido')->default(true);
            $table->string('motivo_exclusion', 255)->nullable();
            $table->timestamps();
            $table->index(['mantenimiento_id', 'incluido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_materiales');
    }
};
