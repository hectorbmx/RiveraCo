<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_empleado_km_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('vehiculo_empleado_id');
            $table->dateTime('fecha'); // cuando se capturó
            $table->unsignedBigInteger('km');
            $table->string('foto'); // ruta en storage
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index(['vehiculo_empleado_id', 'fecha']);

            $table->foreign('vehiculo_empleado_id')
                ->references('id')
                ->on('vehiculo_empleado')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_empleado_km_logs');
    }
};
