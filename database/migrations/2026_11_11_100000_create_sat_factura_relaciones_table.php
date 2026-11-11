<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_factura_relaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sat_factura_id')->constrained('sat_facturas')->restrictOnDelete();
            $table->foreignId('factura_relacionada_id')->nullable()->constrained('sat_facturas')->restrictOnDelete();
            $table->string('tipo_relacion', 2);
            $table->uuid('uuid_relacionado');
            $table->timestamps();

            $table->unique(['sat_factura_id', 'tipo_relacion', 'uuid_relacionado'], 'sat_factura_relacion_unica');
            $table->index('uuid_relacionado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_factura_relaciones');
    }
};
