<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_config', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_config', 'maquinaria_servicio_horas')) {
                $table->unsignedInteger('maquinaria_servicio_horas')->default(250)->after('activa');
            }

            if (!Schema::hasColumn('empresa_config', 'maquinaria_servicio_meses')) {
                $table->unsignedInteger('maquinaria_servicio_meses')->default(6)->after('maquinaria_servicio_horas');
            }

            if (!Schema::hasColumn('empresa_config', 'maquinaria_alerta_horas')) {
                $table->unsignedInteger('maquinaria_alerta_horas')->default(20)->after('maquinaria_servicio_meses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_config', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_config', 'maquinaria_alerta_horas')) {
                $table->dropColumn('maquinaria_alerta_horas');
            }

            if (Schema::hasColumn('empresa_config', 'maquinaria_servicio_meses')) {
                $table->dropColumn('maquinaria_servicio_meses');
            }

            if (Schema::hasColumn('empresa_config', 'maquinaria_servicio_horas')) {
                $table->dropColumn('maquinaria_servicio_horas');
            }
        });
    }
};
