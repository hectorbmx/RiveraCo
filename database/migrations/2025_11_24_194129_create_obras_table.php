<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obras', function (Blueprint $table) {
            $table->id();

            // Relación con cliente
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            $table->string('nombre');
            $table->string('clave_obra')->unique();
            $table->text('descripcion')->nullable();

            // tipo y status
            $table->string('tipo_obra')->nullable(); // luego podemos hacer catálogo
            $table->string('status')->default('planeacion');
            // valores sugeridos: planeacion, ejecucion, suspendida, terminada, cancelada

            // fechas
            $table->date('fecha_inicio_programada')->nullable();
            $table->date('fecha_inicio_real')->nullable();
            $table->date('fecha_fin_programada')->nullable();
            $table->date('fecha_fin_real')->nullable();

            // montos
            $table->decimal('monto_contratado', 15, 2)->nullable();
            $table->decimal('monto_modificado', 15, 2)->nullable();

            // responsable (jefe de obra)
            $table->foreignId('responsable_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ubicacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obras');
    }
};
