<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('sat_facturas', 'amortizacion')) {
                $table->decimal('amortizacion', 15, 2)->default(0)->after('descuento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sat_facturas', function (Blueprint $table) {
            if (Schema::hasColumn('sat_facturas', 'amortizacion')) {
                $table->dropColumn('amortizacion');
            }
        });
    }
};
