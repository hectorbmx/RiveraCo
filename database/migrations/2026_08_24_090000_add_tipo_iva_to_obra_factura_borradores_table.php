<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (! Schema::hasColumn('obra_factura_borradores', 'tipo_iva')) {
                $table->string('tipo_iva', 20)->default('0.16')->after('subtotal');
            }
        });

        if (Schema::hasColumn('obra_factura_borradores', 'tipo_iva')) {
            DB::table('obra_factura_borradores')
                ->whereNull('tipo_iva')
                ->orWhere('tipo_iva', '')
                ->update(['tipo_iva' => '0.16']);

            DB::statement("UPDATE obra_factura_borradores SET tipo_iva = CASE WHEN ROUND(COALESCE(iva_tasa, 0), 6) = 0.080000 THEN '0.08' WHEN ROUND(COALESCE(iva_tasa, 0), 6) = 0.000000 THEN '0' ELSE '0.16' END WHERE tipo_iva IS NULL OR tipo_iva = '' OR tipo_iva = '0.16'");
        }
    }

    public function down(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (Schema::hasColumn('obra_factura_borradores', 'tipo_iva')) {
                $table->dropColumn('tipo_iva');
            }
        });
    }
};
