<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_reposicion_gasto_detalles', function (Blueprint $table) {

            $table->id();

            $table->foreignId('obra_reposicion_gasto_id')
                ->constrained('obra_reposicion_gastos')
                ->cascadeOnDelete();

            $table->foreignId('sat_cfdi_id')
                ->nullable()
                ->constrained('sat_cfdis')
                ->nullOnDelete();

            $table->string('tipo')->nullable();

            $table->string('descripcion')->nullable();

            $table->string('proveedor')->nullable();

            $table->string('rfc', 20)->nullable();

            $table->uuid('uuid')->nullable();

            $table->date('fecha')->nullable();

            $table->decimal('monto', 14, 2)->default(0);

            $table->string('evidencia_path')->nullable();

            $table->timestamps();

            $table->index('sat_cfdi_id');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_reposicion_gasto_detalles');
    }
};