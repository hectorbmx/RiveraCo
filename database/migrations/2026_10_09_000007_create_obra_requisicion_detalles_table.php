<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_requisicion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_requisicion_id')->constrained('obra_requisiciones')->cascadeOnDelete();
            $table->string('item_type', 60)->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('descripcion', 255);
            $table->string('descripcion_snapshot', 255)->nullable();
            $table->string('unidad', 30)->nullable();
            $table->decimal('cantidad_solicitada', 12, 3)->default(0);
            $table->decimal('cantidad_autorizada', 12, 3)->nullable();
            $table->decimal('cantidad_surtida', 12, 3)->default(0);
            $table->string('estado', 40)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->string('surtido_origen_type', 80)->nullable();
            $table->unsignedBigInteger('surtido_origen_id')->nullable();
            $table->unsignedBigInteger('inventario_documento_id')->nullable();
            $table->timestamps();

            $table->index(['obra_requisicion_id', 'estado'], 'obra_req_detalles_req_estado_index');
            $table->index(['item_type', 'item_id'], 'obra_req_detalles_item_index');
            $table->index(['surtido_origen_type', 'surtido_origen_id'], 'obra_req_detalles_surtido_origen_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_requisicion_detalles');
    }
};
