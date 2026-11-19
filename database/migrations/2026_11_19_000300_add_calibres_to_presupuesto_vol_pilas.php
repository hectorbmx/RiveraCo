<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->string('calibre_varilla_l0', 4)->nullable();
            $table->string('calibre_varilla_l1', 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->dropColumn(['calibre_varilla_l0', 'calibre_varilla_l1']);
        });
    }
};
