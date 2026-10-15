<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (!Schema::hasColumn('reposicion_caja_chica_gastos', 'activo_operativo_tipo')) {
                $table->string('activo_operativo_tipo', 20)
                    ->nullable()
                    ->after('vehiculo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (Schema::hasColumn('reposicion_caja_chica_gastos', 'activo_operativo_tipo')) {
                $table->dropColumn('activo_operativo_tipo');
            }
        });
    }
};