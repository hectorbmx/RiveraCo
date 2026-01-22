<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_notas', function (Blueprint $table) {
            $table->id();

            // Relación con empleado
            $table->integer('empleado_id');
            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnDelete();

            // Opcional: quién creó la nota
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Info de la nota
            $table->string('tipo', 50)->nullable();        // aumento, advertencia, reconocimiento, etc.
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 10, 2)->nullable();   // ej. nuevo sueldo, bono, etc.
            $table->date('fecha_evento')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_notas');
    }
};
