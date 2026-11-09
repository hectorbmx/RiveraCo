<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_recepciones_directas', function (Blueprint $table) {
            $table->id();
            $table->uuid('operacion_uuid')->unique('crd_operacion_unique');
            $table->string('folio', 40)->nullable()->unique('crd_folio_unique');
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->restrictOnDelete();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $table->string('estado', 20)->default('borrador');
            $table->date('fecha_recepcion');
            $table->string('receptor_nombre', 160);
            $table->string('remision', 160)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('moneda', 10);
            $table->decimal('tipo_cambio', 18, 8)->default(1);
            $table->decimal('total_oc', 18, 2)->default(0);
            $table->decimal('total_mxn', 18, 2)->default(0);
            $table->foreignId('entrada_documento_id')->nullable()->constrained('inventario_documentos')->restrictOnDelete();
            $table->foreignId('salida_documento_id')->nullable()->constrained('inventario_documentos')->restrictOnDelete();
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmado_at')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revertido_at')->nullable();
            $table->text('motivo_reversion')->nullable();
            $table->timestamps();
            $table->index(['obra_id', 'estado'], 'crd_obra_estado_idx');
            $table->index(['orden_compra_id', 'estado'], 'crd_oc_estado_idx');
        });

        Schema::create('compra_recepcion_directa_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('compra_recepciones_directas')->restrictOnDelete();
            $table->foreignId('orden_compra_detalle_id');
            $table->foreign('orden_compra_detalle_id', 'crdd_oc_detalle_fk')->references('id')->on('orden_compra_detalles')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedBigInteger('obra_requisicion_detalle_id')->nullable();
            $table->foreign('obra_requisicion_detalle_id', 'crdd_requisicion_fk')->references('id')->on('obra_requisicion_detalles')->restrictOnDelete();
            $table->string('descripcion', 500);
            $table->string('unidad', 40)->nullable();
            $table->decimal('cantidad_ordenada', 15, 3);
            $table->decimal('cantidad_recibida', 15, 3);
            $table->decimal('precio_unitario_oc', 18, 8);
            $table->decimal('descuento_oc', 18, 2)->default(0);
            $table->decimal('importe_neto_oc', 18, 2);
            $table->decimal('costo_unitario_oc', 18, 8);
            $table->decimal('costo_unitario_mxn', 18, 8);
            $table->decimal('tipo_cambio', 18, 8)->default(1);
            $table->decimal('importe_oc', 18, 2);
            $table->decimal('importe_mxn', 18, 2);
            $table->timestamps();
            $table->unique(['recepcion_id', 'orden_compra_detalle_id'], 'crdd_recepcion_partida_unique');
        });

        Schema::create('compra_recepcion_directa_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('compra_recepciones_directas')->restrictOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 512)->unique('crda_path_unique');
            $table->string('nombre_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('hash_sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('inventario_documentos', function (Blueprint $table) {
            $table->foreignId('compra_recepcion_directa_id')->nullable();
            $table->foreign('compra_recepcion_directa_id', 'inventario_doc_crd_fk')->references('id')->on('compra_recepciones_directas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventario_documentos', function (Blueprint $table) {
            $table->dropForeign('inventario_doc_crd_fk');
            $table->dropColumn('compra_recepcion_directa_id');
        });
        Schema::dropIfExists('compra_recepcion_directa_archivos');
        Schema::dropIfExists('compra_recepcion_directa_detalles');
        Schema::dropIfExists('compra_recepciones_directas');
    }
};
