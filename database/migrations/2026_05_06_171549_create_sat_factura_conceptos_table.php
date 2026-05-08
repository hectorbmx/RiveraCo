<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatFacturaConceptosTable extends Migration
{
    public function up()
    {
        Schema::create('sat_factura_conceptos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sat_factura_id')
                ->constrained('sat_facturas')
                ->cascadeOnDelete();

            $table->string('descripcion');
            $table->decimal('cantidad', 15, 6)->default(1);
            $table->string('unidad', 50)->nullable();

            // Catálogos SAT
            $table->string('clave_producto_servicio', 20)->nullable(); // product_key
            $table->string('clave_unidad', 20)->nullable(); // unit_key

            $table->decimal('precio_unitario', 15, 6)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('retenciones', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->json('taxes')->nullable();
            $table->json('facturapi_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sat_factura_conceptos');
    }
}