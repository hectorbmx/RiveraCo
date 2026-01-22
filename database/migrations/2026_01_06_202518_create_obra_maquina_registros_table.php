<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('obra_maquina_registros', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('obra_maquina_id');
            $table->unsignedBigInteger('obra_id');
            $table->unsignedBigInteger('maquina_id');

            $table->dateTime('inicio')->nullable();
            $table->dateTime('fin')->nullable();

            $table->decimal('horometro_inicio', 10, 2);
            $table->decimal('horometro_fin', 10, 2);

            $table->decimal('horas', 10, 2)->nullable();

            $table->text('notas')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Índices clave
            $table->index(['obra_maquina_id']);
            $table->index(['obra_id', 'maquina_id']);

            // Foreign keys (si tu proyecto los usa)
            // $table->foreign('obra_maquina_id')->references('id')->on('obra_maquina');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_maquina_registros');
    }
};
