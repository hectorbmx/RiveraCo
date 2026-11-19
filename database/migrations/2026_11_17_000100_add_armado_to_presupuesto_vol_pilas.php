<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->decimal('intervalo_l0', 8, 3)->default(0);
            $table->decimal('intervalo_l1', 8, 3)->default(0);
            $table->decimal('intervalo_l2', 8, 3)->default(0);
            $table->unsignedInteger('varillas_l0')->default(0);
            $table->unsignedInteger('varillas_l1')->default(0);
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->decimal('vol_traslape_varilla', 10, 5)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn('vol_traslape_varilla');
        });

        Schema::table('presupuesto_vol_pilas', function (Blueprint $table) {
            $table->dropColumn(['intervalo_l0', 'intervalo_l1', 'intervalo_l2', 'varillas_l0', 'varillas_l1']);
        });
    }
};
