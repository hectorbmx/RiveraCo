<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_proveedor', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Precio vigente que manejas con este proveedor
            $table->decimal('precio_lista', 12, 4)->nullable();

            // Moneda del precio
            $table->string('moneda', 10)->default('MXN');

            // Tiempo estándar de entrega en días (opcional)
            $table->unsignedSmallInteger('tiempo_entrega_dias')->nullable();

            // Para desactivar una relación sin borrarla
            $table->boolean('activo')->default(true);

            // Notas/condiciones especiales con este proveedor para ese producto
            $table->string('notas', 255)->nullable();

            // Evitar duplicados del mismo producto con el mismo proveedor
            $table->unique(['producto_id', 'proveedor_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_proveedor');
    }
};
