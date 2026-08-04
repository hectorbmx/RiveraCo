<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_viatico_tarifas', function (Blueprint $table) {
            $table->id();
            $table->decimal('importe_diario', 14, 2);
            $table->date('vigencia_desde');
            $table->date('vigencia_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['activo', 'vigencia_desde']);
            $table->index(['vigencia_desde', 'vigencia_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_viatico_tarifas');
    }
};