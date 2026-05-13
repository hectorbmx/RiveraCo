<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuentas_banco_empresa', function (Blueprint $table) {
            $table->id();

            $table->string('nombre')->nullable(); // Ej: Cuenta principal BBVA
            $table->string('banco', 120);
            $table->string('titular')->nullable();
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('clabe', 30)->nullable();
            $table->string('moneda', 10)->default('MXN');

            $table->boolean('activa')->default(true);
            $table->boolean('principal')->default(false);

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_banco_empresas');
    }
};
