<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comision_detalles', function (Blueprint $table) {
            // Metros lineales de ademe Bauer (puede ser decimal)
            $table->decimal('ml_ademe_bauer', 12, 2)->default(0)->after('metros_sujetos_comision');

            // Campana por pieza (conteo)
            $table->unsignedInteger('campana_pzas')->default(0)->after('ml_ademe_bauer');
        });
    }

    public function down(): void
    {
        Schema::table('comision_detalles', function (Blueprint $table) {
            $table->dropColumn(['ml_ademe_bauer', 'campana_pzas']);
        });
    }
};
