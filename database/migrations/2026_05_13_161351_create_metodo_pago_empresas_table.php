<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago_empresa', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->string('clave')->unique();

            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago_empresa');
    }
};