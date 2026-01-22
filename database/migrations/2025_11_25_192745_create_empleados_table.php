<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {

            // Mantenemos el mismo ID que tu sistema anterior
            $table->integer('id_Empleado')->primary();

            $table->string('Nombre', 100)->nullable();
            $table->string('Apellidos', 100)->nullable();
            $table->string('Email', 50)->nullable();

            $table->date('Fecha_nacimiento')->nullable();
            $table->date('Fecha_ingreso')->nullable();
            $table->date('Fecha_baja')->nullable();

            $table->string('Area', 50)->nullable();
            $table->string('Puesto', 50)->nullable();
            $table->string('Telefono', 50)->nullable();
            $table->string('Celular', 50)->nullable();
            $table->string('Direccion', 100)->nullable();
            $table->string('Colonia', 100)->nullable();
            $table->string('Ciudad', 100)->nullable();
            $table->string('CP', 50)->nullable();

            $table->string('RFC', 50)->nullable();
            $table->string('CURP', 50)->nullable();
            $table->string('IMSS', 50)->nullable();
            $table->string('Sangre', 50)->nullable();
            $table->string('Cuenta_banco', 50)->nullable();

            // 💰 Cambiamos a DECIMAL para dinero
            $table->decimal('Sueldo', 10, 2)->nullable();
            $table->decimal('Sueldo_real', 10, 2)->nullable();
            $table->decimal('Complemento', 10, 2)->nullable();

            $table->integer('Sueldo_tipo')->nullable();   // quincenal, semanal, etc.
            $table->integer('listaraya')->nullable();
            $table->string('Horassemana', 50)->nullable();

            $table->decimal('infonavit', 10, 2)->nullable();

            $table->string('Estatus', 50)->nullable();     // ACTIVO, BAJA, etc.
            $table->string('Honorarios', 50)->nullable();
            $table->string('Notas', 200)->nullable();
            $table->string('foto', 200)->nullable();

            // ✅ ahora sí usamos timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
