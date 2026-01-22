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
        Schema::table('clientes', function (Blueprint $table) {
            // Puedes dejar "direccion" como campo libre y agregar los detallados
            $table->string('calle', 150)->nullable()->after('direccion');
            $table->string('colonia', 150)->nullable()->after('calle');
            $table->string('ciudad', 100)->nullable()->after('colonia');
            $table->string('estado', 100)->nullable()->after('ciudad');
            $table->string('pais', 100)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['calle', 'colonia', 'ciudad', 'estado', 'pais']);
        });
    }
};
