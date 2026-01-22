<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_empleado', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->cascadeOnDelete();

            // empleados.id_Empleado (INT)
            $table->integer('empleado_id');

            $table->foreign('empleado_id')
                  ->references('id_Empleado')
                  ->on('empleados')
                  ->onDelete('cascade');

            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_empleado');
    }
};
