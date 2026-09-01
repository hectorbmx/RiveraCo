<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('orden_compra_detalles', 'iva_importe_manual')) {
                $table->decimal('iva_importe_manual', 12, 2)
                    ->nullable()
                    ->after('iva');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('orden_compra_detalles', 'iva_importe_manual')) {
                $table->dropColumn('iva_importe_manual');
            }
        });
    }
};
