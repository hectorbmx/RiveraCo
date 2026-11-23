<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->unsignedTinyInteger('calibre_zuncho_l0')->nullable();
            $table->unsignedTinyInteger('calibre_zuncho_l1')->nullable();
            $table->unsignedTinyInteger('calibre_zuncho_l2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->dropColumn(['calibre_zuncho_l0', 'calibre_zuncho_l1', 'calibre_zuncho_l2']);
        });
    }
};
