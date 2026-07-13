<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            $table->text('concepto_descripcion')->change();
        });
    }

    public function down(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            $table->string('concepto_descripcion')->change();
        });
    }
};