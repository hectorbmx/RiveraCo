<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_planos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->foreignId('plano_categoria_id')->constrained('plano_categorias')->restrictOnDelete();

            $table->string('nombre');
            $table->string('version')->nullable();
            $table->string('archivo_path'); // pdf, cad, png, etc
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_planos');
    }
};
