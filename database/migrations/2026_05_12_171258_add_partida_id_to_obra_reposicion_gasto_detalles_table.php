<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_reposicion_gasto_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('partida_id')->nullable()->after('sat_cfdi_id');

            $table->index('partida_id');
        });
    }

    public function down(): void
    {
        Schema::table('obra_reposicion_gasto_detalles', function (Blueprint $table) {
            $table->dropIndex(['partida_id']);
            $table->dropColumn('partida_id');
        });
    }
};