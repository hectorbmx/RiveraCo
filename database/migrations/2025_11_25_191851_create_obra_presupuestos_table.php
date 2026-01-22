<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_presupuestos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnDelete();

            $table->string('nombre');              // Ej: Presupuesto inicial
            $table->string('version')->nullable(); // Ej: v1, v2, Revisión 3
            $table->date('fecha')->nullable();
            $table->text('notas')->nullable();

            $table->string('archivo_path');        // Ruta del PDF en storage

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_presupuestos');
    }
};
