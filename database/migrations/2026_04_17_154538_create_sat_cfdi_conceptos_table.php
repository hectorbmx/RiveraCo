<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_cfdi_conceptos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sat_cfdi_id')
                ->constrained('sat_cfdis')
                ->cascadeOnDelete();

            $table->string('clave_prod_serv', 20)->nullable();
            $table->string('no_identificacion', 100)->nullable();
            $table->decimal('cantidad', 18, 6)->nullable();
            $table->string('clave_unidad', 20)->nullable();
            $table->string('unidad', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('valor_unitario', 18, 6)->nullable();
            $table->decimal('importe', 18, 6)->nullable();
            $table->decimal('descuento', 18, 6)->nullable();
            $table->string('objeto_impuesto', 10)->nullable();

            $table->json('informacion_aduanera_json')->nullable();
            $table->json('cuenta_predial_json')->nullable();
            $table->json('parte_json')->nullable();
            $table->json('complemento_concepto_json')->nullable();
            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->index('sat_cfdi_id');
            $table->index('clave_prod_serv');
            $table->index('objeto_impuesto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_cfdi_conceptos');
    }
};