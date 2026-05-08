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

    // CFDI 4.0
    $table->string('codigo_postal', 10)->nullable()->after('pais');

    $table->string('regimen_fiscal', 10)->nullable()->after('codigo_postal');

    $table->string('uso_cfdi_default', 10)
        ->nullable()
        ->after('regimen_fiscal');

    // Facturapi
    $table->string('facturapi_customer_id')
        ->nullable()
        ->after('uso_cfdi_default');

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            //
        });
    }
};
