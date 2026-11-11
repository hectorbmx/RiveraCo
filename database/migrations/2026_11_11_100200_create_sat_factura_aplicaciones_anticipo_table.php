<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_factura_aplicaciones_anticipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sat_factura_id')->constrained('sat_facturas')->restrictOnDelete();
            $table->foreignId('factura_anticipo_id')->nullable()->constrained('sat_facturas')->restrictOnDelete();
            $table->uuid('uuid_anticipo');
            // Importe total aplicado, incluidos impuestos, en la moneda de la nota de credito.
            $table->decimal('importe_aplicado', 18, 6);
            $table->timestamps();

            $table->unique(['sat_factura_id', 'uuid_anticipo'], 'sat_factura_anticipo_unico');
            $table->index('uuid_anticipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_factura_aplicaciones_anticipo');
    }
};
