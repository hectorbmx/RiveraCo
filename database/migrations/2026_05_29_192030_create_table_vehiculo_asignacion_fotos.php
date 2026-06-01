<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_asignacion_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_empleado_id')
                  ->constrained('vehiculo_empleado')
                  ->cascadeOnDelete();
            $table->string('url');
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_asignacion_fotos');
    }
};