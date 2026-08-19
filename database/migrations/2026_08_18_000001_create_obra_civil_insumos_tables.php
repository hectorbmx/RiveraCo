<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_civil_insumo_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_path')->nullable();
            $table->string('sheet_name')->nullable();
            $table->string('status', 30)->default('imported');
            $table->unsignedInteger('total_insumos')->default(0);
            $table->unsignedInteger('total_materiales')->default(0);
            $table->unsignedInteger('total_mano_obra')->default(0);
            $table->unsignedInteger('total_equipo_herramienta')->default(0);
            $table->decimal('total_importe', 15, 2)->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['obra_id', 'status']);
        });

        Schema::create('obra_civil_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_civil_insumo_import_id')
                ->constrained('obra_civil_insumo_imports')
                ->cascadeOnDelete();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('tipo', 50)->nullable();
            $table->string('codigo', 100);
            $table->text('concepto');
            $table->string('unidad', 50)->nullable();
            $table->decimal('cantidad_presupuestada', 15, 4)->default(0);
            $table->decimal('precio_unitario', 15, 4)->default(0);
            $table->decimal('importe_importado', 15, 2)->default(0);
            $table->decimal('importe_calculado', 15, 2)->default(0);
            $table->decimal('incidencia', 10, 6)->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['obra_id', 'tipo']);
            $table->index(['obra_id', 'is_active']);
            $table->index('codigo');
            $table->unique(['obra_id', 'tipo', 'codigo'], 'obra_civil_insumos_obra_tipo_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_civil_insumos');
        Schema::dropIfExists('obra_civil_insumo_imports');
    }
};
