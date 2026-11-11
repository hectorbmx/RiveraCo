<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_factura_borradores', function (Blueprint $table) {
            $table->string('tipo_comprobante', 5)->default('I')->index();
        });
    }

    public function down(): void
    {
        Schema::table('sat_factura_borradores', function (Blueprint $table) {
            $table->dropIndex(['tipo_comprobante']);
            $table->dropColumn('tipo_comprobante');
        });
    }
};
