<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('orden_compra_detalles', 'descuento_porcentaje')) {
                $table->decimal('descuento_porcentaje', 5, 2)
                    ->default(0)
                    ->after('precio_unitario');
            }

            if (!Schema::hasColumn('orden_compra_detalles', 'descuento_importe')) {
                $table->decimal('descuento_importe', 12, 2)
                    ->default(0)
                    ->after('descuento_porcentaje');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('orden_compra_detalles', 'descuento_importe')) {
                $table->dropColumn('descuento_importe');
            }

            if (Schema::hasColumn('orden_compra_detalles', 'descuento_porcentaje')) {
                $table->dropColumn('descuento_porcentaje');
            }
        });
    }
};