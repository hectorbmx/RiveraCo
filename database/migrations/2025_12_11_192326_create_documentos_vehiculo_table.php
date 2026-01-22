<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_vehiculo', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->cascadeOnDelete();

            $table->string('tipo', 100); // tarjeta_circulacion, verificacion, etc.
            $table->string('numero', 100)->nullable();

            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            $table->string('archivo')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_vehiculo');
    }
};
