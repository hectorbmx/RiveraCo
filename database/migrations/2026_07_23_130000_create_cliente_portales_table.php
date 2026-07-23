<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_portales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->text('link_acceso');
            $table->string('usuario')->nullable();
            $table->text('password')->nullable();
            $table->timestamps();

            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_portales');
    }
};