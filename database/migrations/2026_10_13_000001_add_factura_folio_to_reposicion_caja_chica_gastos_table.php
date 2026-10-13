<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (!Schema::hasColumn('reposicion_caja_chica_gastos', 'factura_folio')) {
                $table->string('factura_folio', 80)->nullable()->after('proveedor_rfc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reposicion_caja_chica_gastos', function (Blueprint $table) {
            if (Schema::hasColumn('reposicion_caja_chica_gastos', 'factura_folio')) {
                $table->dropColumn('factura_folio');
            }
        });
    }
};
