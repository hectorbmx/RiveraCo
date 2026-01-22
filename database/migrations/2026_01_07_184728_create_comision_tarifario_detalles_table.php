<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comision_tarifario_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tarifario_id')->constrained('comision_tarifarios')->cascadeOnDelete();
            $table->foreignId('trabajo_id')->constrained('catalogo_trabajos_comision')->restrictOnDelete();
            $table->foreignId('rol_id')->constrained('catalogo_roles')->restrictOnDelete();

            // produccion | hora_extra
            $table->string('concepto', 20);

            // keys EXACTAS del origen (comision_detalles / comision_personal)
            // metros_sujetos_comision | kg_acero | vol_bentonita | vol_concreto | campana_pzas | tiempo_extra
            $table->string('variable_origen', 50);

            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();

            $table->decimal('tarifa', 12, 4)->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(
                ['tarifario_id', 'trabajo_id', 'rol_id', 'concepto', 'variable_origen'],
                'uniq_tarifa_detalle'
            );

            $table->index(['trabajo_id', 'rol_id', 'concepto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_tarifario_detalles');
    }
};
