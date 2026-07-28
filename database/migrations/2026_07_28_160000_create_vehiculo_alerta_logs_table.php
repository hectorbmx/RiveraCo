<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_alerta_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('tipo_alerta', 80);
            $table->string('estado', 40);
            $table->unsignedInteger('km_actual')->nullable();
            $table->unsignedInteger('km_proximo_servicio')->nullable();
            $table->integer('km_restantes')->nullable();
            $table->string('hash_contexto', 64);
            $table->unsignedInteger('correos_enviados')->default(0);
            $table->unsignedInteger('notificaciones_enviadas')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['vehiculo_id', 'tipo_alerta', 'hash_contexto'], 'veh_alerta_hash_unique');
            $table->index(['tipo_alerta', 'estado', 'sent_at'], 'veh_alerta_estado_sent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_alerta_logs');
    }
};
