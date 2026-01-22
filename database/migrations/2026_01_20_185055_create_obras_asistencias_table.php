<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('obras_asistencias', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('obra_id');
            $table->integer('empleado_id');

            $table->unsignedBigInteger('registrado_por_user_id');

            $table->enum('tipo', ['entrada', 'salida']);

            // Fecha/hora del dispositivo
            $table->timestamp('checked_at');
            $table->date('checked_date');

            // Foto (entrada obligatoria; salida configurable por validación)
            $table->string('photo_path')->nullable();

            // Ubicación
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('ubicacion_texto', 255)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['obra_id', 'empleado_id', 'checked_date']);
            $table->unique(['obra_id', 'empleado_id', 'checked_date', 'tipo'], 'uniq_asistencia_por_tipo_dia');

            // FKs (ajusta nombres de tablas si difieren en tu proyecto)
            $table->foreign('obra_id')->references('id')->on('obras');
            $table->foreign('empleado_id')->references('id_Empleado')->on('empleados');
            $table->foreign('registrado_por_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obras_asistencias');
    }
};
