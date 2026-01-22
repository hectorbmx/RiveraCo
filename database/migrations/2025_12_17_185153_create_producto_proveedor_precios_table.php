<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('producto_proveedor_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->decimal('precio', 12, 4);
            $table->string('moneda', 10)->default('MXN');
            $table->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra');
            $table->timestamps();

            $table->index(['proveedor_id','producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_proveedor_precios');
    }
};
