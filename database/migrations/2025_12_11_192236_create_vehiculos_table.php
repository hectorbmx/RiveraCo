<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->year('anio')->nullable();
            $table->string('color', 50)->nullable();

            $table->string('placas', 20)->unique();
            $table->string('serie', 50)->unique()->nullable();

            $table->string('tipo', 50)->nullable();
            $table->string('foto_principal')->nullable();

            $table->enum('estatus', ['activo', 'baja', 'en_taller'])
                  ->default('activo');

            $table->date('fecha_registro')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
