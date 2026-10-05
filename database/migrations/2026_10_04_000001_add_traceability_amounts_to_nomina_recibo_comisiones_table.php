<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_recibo_comisiones', function (Blueprint $table) {
            $table->decimal('produccion_monto', 12, 2)->default(0)->after('importe_comision');
            $table->decimal('horas_extra_cantidad', 8, 2)->default(0)->after('tiempo_extra');
            $table->decimal('tarifa_hora_extra_snapshot', 12, 4)->default(0)->after('horas_extra_cantidad');
            $table->decimal('horas_extra_monto', 12, 2)->default(0)->after('tarifa_hora_extra_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('nomina_recibo_comisiones', function (Blueprint $table) {
            $table->dropColumn([
                'produccion_monto',
                'horas_extra_cantidad',
                'tarifa_hora_extra_snapshot',
                'horas_extra_monto',
            ]);
        });
    }
};
