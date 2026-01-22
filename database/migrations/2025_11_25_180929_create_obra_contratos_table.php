<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_contratos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnDelete();

            $table->string('tipo')->nullable(); // principal, modificatorio, ampliación, etc.
            $table->string('nombre')->nullable(); // nombre corto del contrato
            $table->text('descripcion')->nullable();

            $table->decimal('monto_contrato', 15, 2)->nullable();
            $table->date('fecha_firma')->nullable();

            $table->string('archivo_path'); // ruta del PDF en storage

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_contratos');
    }
};
