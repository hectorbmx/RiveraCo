<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();

            // Para saber de qué registro legacy viene
            $table->unsignedInteger('legacy_id')->nullable()->index();

            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('domicilio', 255)->nullable();

            // Mejor como string, por si hay guiones, extensiones, etc.
            $table->string('telefono', 30)->nullable();

            $table->string('email', 150)->nullable();
            $table->string('banco', 100)->nullable();
            $table->string('clabe', 25)->nullable();
            $table->string('cuenta', 50)->nullable();

            $table->boolean('activo')->default(true);

            // Si quieres conservar la fecha original del sistema viejo
            $table->date('fecha_registro')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
