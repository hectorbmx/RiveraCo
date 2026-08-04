<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_reposicion_gasto_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_viatico_tarifa_id')->nullable()->after('sat_cfdi_id');
            $table->unsignedBigInteger('obra_empleado_id')->nullable()->after('empresa_viatico_tarifa_id');
            $table->date('fecha_inicio')->nullable()->after('fecha');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');

            $table->index('empresa_viatico_tarifa_id', 'orgd_viatico_tarifa_idx');
            $table->index('obra_empleado_id', 'orgd_obra_empleado_idx');
            $table->index(['fecha_inicio', 'fecha_fin'], 'orgd_viatico_rango_idx');

            $table->foreign('empresa_viatico_tarifa_id', 'orgd_viatico_tarifa_fk')
                ->references('id')
                ->on('empresa_viatico_tarifas')
                ->nullOnDelete();

            $table->foreign('obra_empleado_id', 'orgd_obra_empleado_fk')
                ->references('id')
                ->on('obra_empleado')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('obra_reposicion_gasto_detalles', function (Blueprint $table) {
            $table->dropForeign('orgd_viatico_tarifa_fk');
            $table->dropForeign('orgd_obra_empleado_fk');
            $table->dropIndex('orgd_viatico_tarifa_idx');
            $table->dropIndex('orgd_obra_empleado_idx');
            $table->dropIndex('orgd_viatico_rango_idx');
            $table->dropColumn([
                'empresa_viatico_tarifa_id',
                'obra_empleado_id',
                'fecha_inicio',
                'fecha_fin',
            ]);
        });
    }
};