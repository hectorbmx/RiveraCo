<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('giralda_horas_extras', function (Blueprint $table) {
            $table->id();
            $table->integer('empleado_id');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->decimal('total_horas', 8, 2)->default(0);
            $table->string('motivo', 255);
            $table->string('responsable_solicita', 150);
            $table->string('responsable_autoriza', 150)->nullable();
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_autorizacion')->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['empleado_id', 'fecha']);
            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giralda_horas_extras');
    }
};
