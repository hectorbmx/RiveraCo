<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquina_horometro_ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquina_id')->constrained('maquinas')->cascadeOnDelete();
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->foreignId('obra_maquina_id')->nullable()->constrained('obra_maquina')->nullOnDelete();
            $table->decimal('horometro_anterior', 10, 2)->nullable();
            $table->decimal('horometro_nuevo', 10, 2);
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->string('tipo', 50)->default('ajuste');
            $table->text('notas')->nullable();
            $table->string('origen', 80)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['maquina_id', 'created_at']);
            $table->index(['obra_id', 'maquina_id']);
            $table->index(['obra_maquina_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquina_horometro_ajustes');
    }
};