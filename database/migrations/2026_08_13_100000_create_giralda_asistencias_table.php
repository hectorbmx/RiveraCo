<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('giralda_asistencias', function (Blueprint $table) {
            $table->id();
            $table->integer('empleado_id');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->date('fecha');
            $table->string('estado', 30)->default('presente');
            $table->string('origen', 30)->default('manual');
            $table->dateTime('entrada_at')->nullable();
            $table->dateTime('salida_at')->nullable();
            $table->foreignId('attendance_device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->unsignedInteger('attendance_enroll_id')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['empleado_id', 'fecha'], 'giralda_asistencias_empleado_fecha_unique');
            $table->index(['fecha', 'estado']);
            $table->index(['origen', 'fecha']);
            $table->index(['attendance_device_id', 'attendance_enroll_id'], 'giralda_asistencias_device_enroll_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giralda_asistencias');
    }
};
