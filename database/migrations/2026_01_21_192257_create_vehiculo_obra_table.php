<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_obra', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('obra_id');

            // OJO: mismo tipo y mismo nombre que empleados.id_Empleado
            $table->integer('empleado_id')->nullable();

            $table->date('fecha_inicio');
            $table->unsignedBigInteger('km_inicio');

            $table->date('fecha_fin')->nullable();
            $table->unsignedBigInteger('km_fin')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['vehiculo_id', 'fecha_inicio']);
            $table->index(['obra_id', 'fecha_inicio']);

            // Foreign keys
            $table->foreign('vehiculo_id')
                  ->references('id')->on('vehiculos');

            $table->foreign('obra_id')
                  ->references('id')->on('obras');

            $table->foreign('empleado_id')
                  ->references('id_Empleado')->on('empleados');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_obra');
    }
};
