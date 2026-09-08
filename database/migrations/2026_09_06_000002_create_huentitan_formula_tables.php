<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('huentitan_formula_materiales');
        Schema::dropIfExists('huentitan_formulas');

        Schema::create('huentitan_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->decimal('cantidad_base', 14, 3)->default(1);
            $table->string('unidad_base', 50)->nullable();
            $table->decimal('merma_esperada_porcentaje', 8, 3)->default(0);
            $table->unsignedInteger('tiempo_estimado_minutos')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->unique('producto_id', 'hf_producto_unique');
        });

        Schema::create('huentitan_formula_materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained('huentitan_formulas')->cascadeOnDelete();
            $table->foreignId('material_producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 14, 3);
            $table->string('unidad', 50)->nullable();
            $table->decimal('merma_porcentaje', 8, 3)->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->unique(['formula_id', 'material_producto_id'], 'hfm_formula_material_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_formula_materiales');
        Schema::dropIfExists('huentitan_formulas');
    }
};
