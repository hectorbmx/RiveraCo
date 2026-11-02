<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquina_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquina_id')->constrained('maquinas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo', 50);
            $table->text('descripcion')->nullable();
            $table->string('nombre_original');
            $table->string('archivo_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['maquina_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquina_documentos');
    }
};
