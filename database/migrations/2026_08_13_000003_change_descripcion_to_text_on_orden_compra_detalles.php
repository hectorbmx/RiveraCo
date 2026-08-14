<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->change();
        });
    }
};