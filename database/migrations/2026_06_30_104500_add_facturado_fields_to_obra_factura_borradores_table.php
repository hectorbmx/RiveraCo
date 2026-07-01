<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (!Schema::hasColumn('obra_factura_borradores', 'facturado_por')) {
                $table->foreignId('facturado_por')->nullable()->after('rechazado_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('obra_factura_borradores', 'facturado_at')) {
                $table->timestamp('facturado_at')->nullable()->after('facturado_por');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obra_factura_borradores', function (Blueprint $table) {
            if (Schema::hasColumn('obra_factura_borradores', 'facturado_por')) {
                $table->dropConstrainedForeignId('facturado_por');
            }

            if (Schema::hasColumn('obra_factura_borradores', 'facturado_at')) {
                $table->dropColumn('facturado_at');
            }
        });
    }
};
