<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            $table->foreignId('formula_id')->nullable()->after('producto_id');
            $table->decimal('formula_cantidad_base', 14, 3)->nullable()->after('cantidad_solicitada');
            $table->string('formula_unidad_base', 50)->nullable()->after('formula_cantidad_base');
            $table->decimal('formula_merma_esperada_porcentaje', 8, 3)->default(0)->after('formula_unidad_base');
            $table->unsignedInteger('formula_tiempo_estimado_minutos')->nullable()->after('formula_merma_esperada_porcentaje');
            $table->text('formula_notas')->nullable()->after('formula_tiempo_estimado_minutos');
            $table->decimal('costo_material_estimado', 14, 4)->default(0)->after('formula_notas');

            $table->foreign('formula_id', 'hof_formula_fk')->references('id')->on('huentitan_formulas')->nullOnDelete();
        });

        Schema::create('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_fabricacion_id');
            $table->unsignedBigInteger('formula_material_id')->nullable();
            $table->unsignedBigInteger('material_producto_id')->nullable();
            $table->string('material_sku', 80)->nullable();
            $table->string('material_nombre');
            $table->decimal('cantidad_por_unidad', 14, 3);
            $table->decimal('cantidad_requerida', 14, 3);
            $table->string('unidad', 50)->nullable();
            $table->decimal('merma_porcentaje', 8, 3)->default(0);
            $table->decimal('costo_unitario_estimado', 14, 4)->default(0);
            $table->decimal('costo_total_estimado', 14, 4)->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('orden_fabricacion_id', 'hofm_orden_index');
            $table->index('material_producto_id', 'hofm_material_index');
            $table->foreign('orden_fabricacion_id', 'hofm_orden_fk')->references('id')->on('huentitan_ordenes_fabricacion')->cascadeOnDelete();
            $table->foreign('formula_material_id', 'hofm_formula_material_fk')->references('id')->on('huentitan_formula_materiales')->nullOnDelete();
            $table->foreign('material_producto_id', 'hofm_producto_fk')->references('id')->on('productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_orden_fabricacion_materiales');

        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            $table->dropForeign('hof_formula_fk');
            $table->dropColumn([
                'formula_id',
                'formula_cantidad_base',
                'formula_unidad_base',
                'formula_merma_esperada_porcentaje',
                'formula_tiempo_estimado_minutos',
                'formula_notas',
                'costo_material_estimado',
            ]);
        });
    }
};
