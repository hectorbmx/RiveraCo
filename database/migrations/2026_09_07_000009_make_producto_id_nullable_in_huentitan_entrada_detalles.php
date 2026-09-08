<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('huentitan_entrada_detalles')) {
            Schema::table('huentitan_entrada_detalles', function (Blueprint $table) {
                $table->unsignedBigInteger('producto_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('huentitan_entrada_detalles')) {
            Schema::table('huentitan_entrada_detalles', function (Blueprint $table) {
                $table->unsignedBigInteger('producto_id')->nullable(false)->change();
            });
        }
    }
};
