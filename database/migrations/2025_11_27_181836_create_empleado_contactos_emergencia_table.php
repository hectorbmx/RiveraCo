<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_contactos_emergencia', function (Blueprint $table) {
            $table->id();

            $table->integer('empleado_id');
            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnDelete();

            $table->string('nombre', 100);
            $table->string('parentesco', 50)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('celular', 50)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_contactos_emergencia');
    }
};
