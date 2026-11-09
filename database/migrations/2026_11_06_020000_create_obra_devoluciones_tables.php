<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('obra_devoluciones', function (Blueprint $t) {
            $t->id(); $t->string('folio', 40)->nullable()->unique();
            $t->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $t->string('estado', 24)->default('borrador'); $t->text('observaciones')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('presentado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('presentado_at')->nullable();
            $t->foreignId('cancelado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('cancelado_at')->nullable(); $t->text('motivo_cancelacion')->nullable(); $t->timestamps();
            $t->index(['obra_id', 'estado'], 'od_obra_estado_idx');
        });
        Schema::create('obra_devolucion_detalles', function (Blueprint $t) {
            $t->id(); $t->foreignId('devolucion_id')->constrained('obra_devoluciones')->restrictOnDelete();
            $t->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $t->unsignedBigInteger('recepcion_directa_detalle_id')->nullable();
            $t->foreign('recepcion_directa_detalle_id', 'odd_directa_fk')->references('id')->on('compra_recepcion_directa_detalles')->restrictOnDelete();
            $t->unsignedBigInteger('inventario_movimiento_id')->nullable();
            $t->foreign('inventario_movimiento_id', 'odd_movimiento_fk')->references('id')->on('inventario_movimientos')->restrictOnDelete();
            $t->unsignedBigInteger('huentitan_salida_detalle_id')->nullable();
            $t->foreign('huentitan_salida_detalle_id', 'odd_huentitan_fk')->references('id')->on('huentitan_salida_detalles')->restrictOnDelete();
            $t->string('descripcion', 500); $t->string('unidad', 40); $t->decimal('cantidad_solicitada', 15, 3);
            $t->decimal('cantidad_entregada_snapshot', 15, 3); $t->decimal('costo_unitario_mxn', 18, 8);
            $t->decimal('importe_origen_mxn', 18, 2); $t->string('condicion_declarada', 40); $t->text('observaciones')->nullable(); $t->timestamps();
        });
        $check = '((recepcion_directa_detalle_id IS NOT NULL) + (inventario_movimiento_id IS NOT NULL) + (huentitan_salida_detalle_id IS NOT NULL)) = 1';
        if (DB::getDriverName() === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $action) {
                $expr = str_replace(['recepcion_directa_detalle_id', 'inventario_movimiento_id', 'huentitan_salida_detalle_id'], ['NEW.recepcion_directa_detalle_id', 'NEW.inventario_movimiento_id', 'NEW.huentitan_salida_detalle_id'], $check);
                DB::unprepared('CREATE TRIGGER odd_origen_'.strtolower($action).' BEFORE '.$action.' ON obra_devolucion_detalles WHEN NOT ('.$expr.") BEGIN SELECT RAISE(ABORT, 'Debe existir exactamente un origen'); END");
            }
        } else {
            DB::statement('ALTER TABLE obra_devolucion_detalles ADD CONSTRAINT odd_origen_unico CHECK ('.$check.')');
        }
        Schema::create('obra_devolucion_recepciones', function (Blueprint $t) {
            $t->id(); $t->foreignId('devolucion_id')->constrained('obra_devoluciones')->restrictOnDelete();
            $t->uuid('operacion_uuid')->unique('odr_operacion_unique'); $t->string('folio', 40)->nullable()->unique();
            $t->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $t->date('fecha_recepcion'); $t->string('estado', 20)->default('borrador');
            $t->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('confirmado_at')->nullable(); $t->text('observaciones')->nullable(); $t->timestamps();
        });
        Schema::create('obra_devolucion_recepcion_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('recepcion_id');
            $t->foreign('recepcion_id', 'odrd_recepcion_fk')->references('id')->on('obra_devolucion_recepciones')->restrictOnDelete();
            $t->unsignedBigInteger('devolucion_detalle_id');
            $t->foreign('devolucion_detalle_id', 'odrd_detalle_fk')->references('id')->on('obra_devolucion_detalles')->restrictOnDelete();
            $t->decimal('cantidad_recibida', 15, 3); $t->string('condicion_recibida', 40); $t->text('observaciones')->nullable();
            $t->string('estado_evaluacion', 40)->nullable(); $t->decimal('costo_unitario_mxn', 18, 8); $t->decimal('importe_mxn', 18, 2)->nullable();
            $t->unsignedBigInteger('inventario_documento_detalle_id')->nullable();
            $t->foreign('inventario_documento_detalle_id', 'odrd_doc_det_fk')->references('id')->on('inventario_documento_detalles')->restrictOnDelete();
            $t->unsignedBigInteger('inventario_movimiento_id')->nullable();
            $t->foreign('inventario_movimiento_id', 'odrd_mov_fk')->references('id')->on('inventario_movimientos')->restrictOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('obra_devolucion_recepcion_detalles');
        Schema::dropIfExists('obra_devolucion_recepciones');
        Schema::dropIfExists('obra_devolucion_detalles');
        Schema::dropIfExists('obra_devoluciones');
    }
};
