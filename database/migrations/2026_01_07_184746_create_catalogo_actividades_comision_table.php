<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_actividades_comision', function (Blueprint $table) {
            $table->id();

            // key = variable_origen de produccion (SIN tiempo_extra)
            // metros_sujetos_comision | kg_acero | vol_bentonita | vol_concreto | campana_pzas
            $table->string('key', 50)->unique();

            $table->string('nombre', 120);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();

            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activa')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_actividades_comision');
    }
};
