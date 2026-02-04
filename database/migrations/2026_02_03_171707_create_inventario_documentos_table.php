<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventario_documentos', function (Blueprint $table) {
            $table->id();

            // Folio interno (opcional: lo puedes usar después para formato INV-000123)
            $table->string('folio', 30)->nullable()->unique();

            $table->enum('tipo', ['inicial','entrada','salida','ajuste','resguardo','devolucion']);

            $table->unsignedBigInteger('almacen_id');

            // Centro de costo / destino (para salidas y resguardos)
            $table->unsignedBigInteger('obra_id')->nullable();

            // Referencias (por ahora nulleables por tu fase)
            $table->unsignedBigInteger('orden_compra_id')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();

            // Control operativo
            $table->enum('estado', ['borrador','aplicado','cancelado'])->default('borrador');
            $table->dateTime('fecha');

            // Motivo general (para ajuste/salida uso almacén, etc.)
            $table->string('motivo', 120)->nullable();

            // Permisos / auditoría
            $table->unsignedBigInteger('creado_por')->nullable(); // user id
            $table->text('notas')->nullable();

            // Resguardo: residente responsable (derivado de obra; lo guardamos para snapshot histórico)
            $table->unsignedBigInteger('residente_id')->nullable();

            // Si documento es devolución, puede referenciar al resguardo original (documento tipo resguardo)
            $table->unsignedBigInteger('documento_origen_id')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['tipo', 'estado', 'fecha']);
            $table->index('almacen_id');
            $table->index('obra_id');
            $table->index('orden_compra_id');
            $table->index('proveedor_id');
            $table->index('residente_id');
            $table->index('documento_origen_id');

            // FKs (déjalas comentadas si no estás 100% seguro de los nombres/PKs)
            // $table->foreign('almacen_id')->references('id')->on('almacenes');
            // $table->foreign('obra_id')->references('id')->on('obras')->nullOnDelete();
            // $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra')->nullOnDelete();
            // $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
            // $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('residente_id')->references('id')->on('empleados')->nullOnDelete();

            // Relación documento origen (ej. devolución -> resguardo)
            // $table->foreign('documento_origen_id')->references('id')->on('inventario_documentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_documentos');
    }
};
