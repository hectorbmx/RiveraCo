<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_app', function (Blueprint $table) {
            $table->id();

            // Relación con empleados (empleados.id_Empleado es INT, firmado)
            $table->integer('empleado_id');

            // Seguridad / activación
            $table->string('password')->nullable();          // hash
            $table->string('activation_token', 64)->nullable();
            $table->timestamp('activated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Evitar duplicados por empleado
            $table->unique('empleado_id');

            // FK correcta a empleados.id_Empleado
            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_app');
    }
};
