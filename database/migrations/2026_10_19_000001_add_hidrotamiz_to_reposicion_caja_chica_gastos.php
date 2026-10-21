<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reposicion_caja_chica_gastos', 'es_para_hidrotamiz')) {
            Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
                $table->boolean('es_para_hidrotamiz')->default(false)->after('activo_operativo_tipo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reposicion_caja_chica_gastos', 'es_para_hidrotamiz')) {
            Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
                $table->dropColumn('es_para_hidrotamiz');
            });
        }
    }
};
