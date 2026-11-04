<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquina_preventivo_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquina_id')->constrained('maquinas');
            $table->foreignId('servicio_preventivo_tipo_id')->nullable();
            $table->foreign('servicio_preventivo_tipo_id', 'maquina_prev_tipo_fk')->references('id')->on('empresa_servicio_preventivo_tipos');
            $table->decimal('horometro', 10, 2);
            $table->dateTime('fecha_referencia');
            $table->string('motivo', 1000);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['maquina_id', 'servicio_preventivo_tipo_id'], 'maquina_preventivo_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquina_preventivo_referencias');
    }
};
