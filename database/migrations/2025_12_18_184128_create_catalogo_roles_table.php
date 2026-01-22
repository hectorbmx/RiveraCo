<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_roles', function (Blueprint $table) {
            $table->id();
            $table->string('rol_key', 60)->unique();       // PERFORADOR, AYUDANTE_PERFORADOR...
            $table->string('nombre', 120);                 // Perforador, Ayudante perforador...
            $table->boolean('comisionable')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_roles');
    }
};
