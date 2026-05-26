<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_computo_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_computo_id')->constrained('equipos_computo')->cascadeOnDelete();
            $table->foreignId('equipo_computo_movimiento_id')->nullable()->constrained('equipo_computo_movimientos')->nullOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('equipo_computo_id');
            $table->index('equipo_computo_movimiento_id', 'eq_comp_fotos_movimiento_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_computo_fotos');
    }
};
