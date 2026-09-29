<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empresa_servicio_preventivo_tipos')) {
            return;
        }

        Schema::create('empresa_servicio_preventivo_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('ambito', 30);
            $table->string('nombre', 100);
            $table->string('codigo', 80);
            $table->string('unidad', 20);
            $table->unsignedInteger('intervalo_valor');
            $table->unsignedInteger('intervalo_meses')->nullable();
            $table->unsignedInteger('alerta_valor')->nullable();
            $table->unsignedInteger('alerta_dias')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['ambito', 'codigo'], 'esp_tipo_ambito_codigo_unique');
            $table->index(['ambito', 'activo', 'orden'], 'esp_tipo_ambito_activo_orden_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_servicio_preventivo_tipos');
    }
};
