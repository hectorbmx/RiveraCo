<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
      Schema::create('empresa_documento_tipos', function (Blueprint $table) {
    $table->id();

    $table->foreignId('empresa_config_id')
        ->constrained('empresa_config')
        ->cascadeOnDelete();

    $table->string('codigo', 80);
    $table->string('nombre', 150);
    $table->text('descripcion')->nullable();

    $table->boolean('obligatorio')->default(false);
    $table->boolean('requiere_vencimiento')->default(false);
    $table->boolean('activo')->default(true);

    $table->unsignedInteger('orden')->default(0);

    $table->timestamps();
    $table->softDeletes();

    $table->unique(['empresa_config_id', 'codigo']);
});
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_documento_tipos');
    }
};