<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_config', function (Blueprint $table) {
            $table->id();

            // Datos generales / fiscales
            $table->string('razon_social', 200)->nullable();
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('domicilio_fiscal', 255)->nullable();

            // Defaults financieros
            $table->string('moneda_base', 3)->default('MXN'); // MXN/USD/EUR
            $table->decimal('iva_por_defecto', 6, 2)->default(16.00);

            // Branding (para PDFs)
            $table->string('logo_path', 255)->nullable();

            // Control general
            $table->boolean('activa')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_config');
    }
};
