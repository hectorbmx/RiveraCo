<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (! Schema::hasColumn('obra_factura_borradores', 'iva_tasa')) {
                $table->decimal('iva_tasa', 8, 6)->default(0.160000)->after('subtotal');
            }

            if (! Schema::hasColumn('obra_factura_borradores', 'retencion_tipo')) {
                $table->string('retencion_tipo', 50)->nullable()->after('iva');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (Schema::hasColumn('obra_factura_borradores', 'retencion_tipo')) {
                $table->dropColumn('retencion_tipo');
            }

            if (Schema::hasColumn('obra_factura_borradores', 'iva_tasa')) {
                $table->dropColumn('iva_tasa');
            }
        });
    }
};