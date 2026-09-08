<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'tipo_inventario')) {
                $table->string('tipo_inventario', 40)->default('materia_prima')->after('unidad');
            }

            if (! Schema::hasColumn('productos', 'origen_abastecimiento')) {
                $table->string('origen_abastecimiento', 30)->default('compra')->after('tipo_inventario');
            }

            if (! Schema::hasColumn('productos', 'requiere_formula')) {
                $table->boolean('requiere_formula')->default(false)->after('origen_abastecimiento');
            }
        });

        Schema::create('inventario_cortes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->date('fecha_desde');
            $table->date('fecha_hasta');
            $table->string('titulo')->nullable();
            $table->string('archivo_original')->nullable();
            $table->string('hoja_importada')->nullable();
            $table->string('estado', 30)->default('borrador');
            $table->unsignedInteger('total_productos')->default(0);
            $table->unsignedInteger('total_con_existencia')->default(0);
            $table->decimal('valor_total_actual', 16, 4)->default(0);
            $table->foreignId('documento_id')->nullable()->constrained('inventario_documentos');
            $table->foreignId('importado_por')->nullable()->constrained('users');
            $table->foreignId('aplicado_por')->nullable()->constrained('users');
            $table->timestamp('aplicado_at')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['almacen_id', 'fecha_desde', 'fecha_hasta'], 'inventario_cortes_periodo_unique');
        });

        Schema::create('inventario_corte_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corte_id')->constrained('inventario_cortes')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos');
            $table->unsignedInteger('numero_importacion')->nullable();
            $table->string('nombre_producto');
            $table->string('unidad', 80)->nullable();
            $table->decimal('existencia_anterior', 14, 3)->default(0);
            $table->decimal('entrada_periodo', 14, 3)->default(0);
            $table->decimal('salida_periodo', 14, 3)->default(0);
            $table->decimal('existencia_actual', 14, 3)->default(0);
            $table->decimal('precio_unitario', 14, 4)->default(0);
            $table->decimal('valor_anterior', 16, 4)->default(0);
            $table->decimal('valor_actual', 16, 4)->default(0);
            $table->string('tipo_inventario', 40)->default('materia_prima');
            $table->string('origen_abastecimiento', 30)->default('compra');
            $table->boolean('requiere_formula')->default(false);
            $table->boolean('es_danado')->default(false);
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['corte_id', 'tipo_inventario']);
            $table->index(['producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_corte_detalles');
        Schema::dropIfExists('inventario_cortes');

        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'requiere_formula')) {
                $table->dropColumn('requiere_formula');
            }

            if (Schema::hasColumn('productos', 'origen_abastecimiento')) {
                $table->dropColumn('origen_abastecimiento');
            }
        });
    }
};