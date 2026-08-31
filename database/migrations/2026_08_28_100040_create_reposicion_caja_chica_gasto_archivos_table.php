<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposicion_caja_chica_gasto_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_id')->constrained('reposicion_caja_chica_gastos')->cascadeOnDelete();
            $table->string('tipo', 40)->default('evidencia');
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('hash_sha256', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('hash_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_caja_chica_gasto_archivos');
    }
};
