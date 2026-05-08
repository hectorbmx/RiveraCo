<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatConceptosTable extends Migration
{
    public function up()
    {
        Schema::create('sat_conceptos', function (Blueprint $table) {

            $table->id();

            // Identificación interna
            $table->string('codigo')->nullable()->index();

            // SAT
            $table->string('clave_producto_servicio', 20);
            $table->string('clave_unidad', 20);

            // Descripción
            $table->string('descripcion');

            // Unidad visible
            $table->string('unidad', 100)->nullable();

            // CFDI
            $table->string('objeto_impuesto', 10)
                ->default('02');
            // 01=no objeto
            // 02=si objeto
            // 03=si objeto y no obligado desglose

            // Impuestos
            $table->decimal('iva_tasa', 8, 6)
                ->default(0.160000);

            $table->boolean('incluye_iva')
                ->default(false);

            // Precios sugeridos
            $table->decimal('precio_unitario', 15, 2)
                ->default(0);

            // Config
            $table->boolean('activo')
                ->default(true);

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sat_conceptos');
    }
}