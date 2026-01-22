<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguros_vehiculos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->cascadeOnDelete();

            $table->string('aseguradora', 150)->nullable();
            $table->string('numero_poliza', 100)->nullable();
            $table->string('tipo_cobertura', 100)->nullable();

            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            $table->decimal('costo_anual', 12, 2)->default(0);

            $table->string('archivo_poliza')->nullable();

            $table->enum('estatus', ['activa', 'vencida', 'cancelada'])
                  ->default('activa');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguros_vehiculos');
    }
};
