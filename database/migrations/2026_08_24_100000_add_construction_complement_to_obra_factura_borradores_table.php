<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (! Schema::hasColumn('obra_factura_borradores', 'usar_complemento_construccion')) {
                $table->boolean('usar_complemento_construccion')->default(false)->after('descuentos');
            }

            if (! Schema::hasColumn('obra_factura_borradores', 'complemento_construccion')) {
                $table->json('complemento_construccion')->nullable()->after('usar_complemento_construccion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (Schema::hasColumn('obra_factura_borradores', 'complemento_construccion')) {
                $table->dropColumn('complemento_construccion');
            }

            if (Schema::hasColumn('obra_factura_borradores', 'usar_complemento_construccion')) {
                $table->dropColumn('usar_complemento_construccion');
            }
        });
    }
};