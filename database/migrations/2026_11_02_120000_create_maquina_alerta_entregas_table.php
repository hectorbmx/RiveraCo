<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maquina_alerta_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquina_id')->constrained('maquinas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('servicio_clave', 40);
            $table->date('fecha_alerta');
            $table->timestamps();
            $table->unique(['maquina_id', 'user_id', 'servicio_clave', 'fecha_alerta'], 'maquina_alerta_entrega_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquina_alerta_entregas');
    }
};
