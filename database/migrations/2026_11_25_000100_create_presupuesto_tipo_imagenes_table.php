<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_tipo_imagenes', function (Blueprint $table) {
            $table->string('tipo', 16)->primary();
            $table->string('archivo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_tipo_imagenes');
    }
};
