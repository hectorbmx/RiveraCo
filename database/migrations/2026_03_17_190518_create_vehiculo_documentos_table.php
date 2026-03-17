<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();

            $table->string('tipo', 50)->default('tarjeta_circulacion');
            $table->string('nombre_original')->nullable();
            $table->string('archivo_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano')->nullable();

            $table->date('fecha_documento')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            $table->boolean('vigente')->default(true);
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['vehiculo_id', 'tipo']);
            $table->index(['vehiculo_id', 'vigente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_documentos');
    }
};