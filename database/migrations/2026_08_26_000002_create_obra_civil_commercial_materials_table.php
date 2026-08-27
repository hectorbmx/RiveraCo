<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_civil_commercial_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('obra_civil_material_group_id');
            $table->string('category', 100)->nullable();
            $table->string('subcategory', 100)->nullable();
            $table->string('grade', 100)->nullable();
            $table->string('sku', 100)->unique();
            $table->string('descripcion', 500);
            $table->string('medida', 100)->nullable();
            $table->string('diametro', 50)->nullable();
            $table->string('calibre_espesor', 100)->nullable();
            $table->decimal('longitud', 12, 4)->nullable();
            $table->string('unidad_compra', 50);
            $table->string('conversion_type', 50);
            $table->decimal('peso_por_metro', 15, 6)->nullable();
            $table->decimal('peso_por_pieza', 15, 6)->nullable();
            $table->decimal('peso_por_m2', 15, 6)->nullable();
            $table->decimal('peso_por_rollo', 15, 6)->nullable();
            $table->decimal('factor_conversion', 15, 6)->nullable();
            $table->string('tolerance', 50)->nullable();
            $table->string('validation_status', 100)->nullable();
            $table->text('technical_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('obra_civil_material_group_id', 'occm_group_fk')
                ->references('id')
                ->on('obra_civil_material_groups')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->index(['obra_civil_material_group_id', 'is_active'], 'occm_group_active_idx');
            $table->index(['category', 'subcategory'], 'occm_category_subcategory_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_civil_commercial_materials');
    }
};


