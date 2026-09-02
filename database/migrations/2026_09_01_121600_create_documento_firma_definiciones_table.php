<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_firma_definiciones', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 80);
            $table->string('documento_label', 150);
            $table->string('ambito', 80)->default('general');
            $table->string('ambito_label', 150);
            $table->string('campo', 80);
            $table->string('campo_label', 150);
            $table->unsignedSmallInteger('orden')->default(100);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['documento', 'ambito', 'campo'], 'documento_firma_def_unique');
            $table->index(['documento', 'ambito', 'activo'], 'documento_firma_def_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_firma_definiciones');
    }
};