<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('herramientas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('codigo', 50);
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('costo', 12, 2)->default(0);
            $table->unsignedInteger('vida_util_piezas')->nullable();
            $table->decimal('costo_residual', 12, 2)->default(0);
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->string('proveedor_nombre', 150)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->enum('estado', ['activa', 'en_mantenimiento', 'baja'])->default('activa');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['almacen_id', 'codigo']);
            $table->index(['almacen_id', 'activo']);
            $table->index(['almacen_id', 'estado']);
            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herramientas');
    }
};

