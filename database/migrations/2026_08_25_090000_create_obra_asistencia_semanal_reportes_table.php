<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_asistencia_semanal_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->foreignId('generado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estatus', 30)->default('borrador');
            $table->timestamp('generado_at')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamp('autorizado_at')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['obra_id', 'semana_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_asistencia_semanal_reportes');
    }
};
