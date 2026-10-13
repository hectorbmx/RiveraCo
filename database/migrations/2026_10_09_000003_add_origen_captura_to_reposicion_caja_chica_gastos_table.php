<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (! Schema::hasColumn('reposicion_caja_chica_gastos', 'origen_captura')) {
                $table->string('origen_captura', 40)
                    ->default('almacen')
                    ->after('almacen_id');

                $table->index('origen_captura', 'rcc_gastos_origen_captura_index');
            }
        });

        DB::table('reposicion_caja_chica_gastos')
            ->whereNull('origen_captura')
            ->orWhere('origen_captura', '')
            ->update(['origen_captura' => 'almacen']);
    }

    public function down(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (Schema::hasColumn('reposicion_caja_chica_gastos', 'origen_captura')) {
                $table->dropIndex('rcc_gastos_origen_captura_index');
                $table->dropColumn('origen_captura');
            }
        });
    }
};
