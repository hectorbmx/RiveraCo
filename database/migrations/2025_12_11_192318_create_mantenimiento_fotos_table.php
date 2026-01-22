<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_fotos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('mantenimiento_id')
                  ->constrained('mantenimientos')
                  ->cascadeOnDelete();

            $table->string('ruta');
            $table->string('descripcion', 200)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_fotos');
    }
};
