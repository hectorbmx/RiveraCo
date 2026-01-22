<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_trabajos_comision', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();     // PERFORACION, COLADO...
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_trabajos_comision');
    }
};
