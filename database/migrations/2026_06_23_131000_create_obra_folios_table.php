<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_folios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_obra', 30);
            $table->string('prefijo', 10);
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('ultimo_consecutivo')->default(0);
            $table->timestamps();

            $table->unique(['tipo_obra', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_folios');
    }
};
