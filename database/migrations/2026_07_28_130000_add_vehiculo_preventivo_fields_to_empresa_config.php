<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_config', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_config', 'vehiculo_servicio_km')) {
                $table->unsignedInteger('vehiculo_servicio_km')->default(5000)->after('maquinaria_alerta_horas');
            }

            if (!Schema::hasColumn('empresa_config', 'vehiculo_servicio_meses')) {
                $table->unsignedInteger('vehiculo_servicio_meses')->default(6)->after('vehiculo_servicio_km');
            }

            if (!Schema::hasColumn('empresa_config', 'vehiculo_alerta_km')) {
                $table->unsignedInteger('vehiculo_alerta_km')->default(500)->after('vehiculo_servicio_meses');
            }

            if (!Schema::hasColumn('empresa_config', 'vehiculo_alerta_dias')) {
                $table->unsignedInteger('vehiculo_alerta_dias')->default(10)->after('vehiculo_alerta_km');
            }

            if (!Schema::hasColumn('empresa_config', 'vehiculo_alertas_activas')) {
                $table->boolean('vehiculo_alertas_activas')->default(true)->after('vehiculo_alerta_dias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_config', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_config', 'vehiculo_alertas_activas')) {
                $table->dropColumn('vehiculo_alertas_activas');
            }

            if (Schema::hasColumn('empresa_config', 'vehiculo_alerta_dias')) {
                $table->dropColumn('vehiculo_alerta_dias');
            }

            if (Schema::hasColumn('empresa_config', 'vehiculo_alerta_km')) {
                $table->dropColumn('vehiculo_alerta_km');
            }

            if (Schema::hasColumn('empresa_config', 'vehiculo_servicio_meses')) {
                $table->dropColumn('vehiculo_servicio_meses');
            }

            if (Schema::hasColumn('empresa_config', 'vehiculo_servicio_km')) {
                $table->dropColumn('vehiculo_servicio_km');
            }
        });
    }
};
