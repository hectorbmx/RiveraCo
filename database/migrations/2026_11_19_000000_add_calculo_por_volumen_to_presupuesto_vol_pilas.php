<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->decimal('volumen_objetivo', 14, 5)->nullable();
            $table->decimal('avance_metros_por_jornal', 14, 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->dropColumn(['volumen_objetivo', 'avance_metros_por_jornal']);
        });
    }
};
