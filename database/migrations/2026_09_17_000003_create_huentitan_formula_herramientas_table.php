<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('huentitan_formula_herramientas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained('huentitan_formulas')->cascadeOnDelete();
            $table->foreignId('herramienta_id')->constrained('herramientas')->restrictOnDelete();
            $table->decimal('cantidad', 12, 3)->default(1);
            $table->decimal('costo_unitario_aplicado', 12, 4)->default(0);
            $table->string('metodo_calculo', 40)->default('manual');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['formula_id', 'herramienta_id']);
            $table->index('herramienta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_formula_herramientas');
    }
};
