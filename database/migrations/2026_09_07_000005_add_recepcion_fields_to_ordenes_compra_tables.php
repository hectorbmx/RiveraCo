<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ordenes_compra')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                if (! Schema::hasColumn('ordenes_compra', 'estado_recepcion')) {
                    $table->string('estado_recepcion', 20)->default('pendiente')->after('estado')->index('oc_estado_recepcion_idx');
                }
            });
        }

        if (Schema::hasTable('orden_compra_detalles')) {
            Schema::table('orden_compra_detalles', function (Blueprint $table) {
                if (! Schema::hasColumn('orden_compra_detalles', 'cantidad_recibida')) {
                    $table->decimal('cantidad_recibida', 14, 3)->default(0)->after('cantidad');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ordenes_compra')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                if (Schema::hasColumn('ordenes_compra', 'estado_recepcion')) {
                    $table->dropIndex('oc_estado_recepcion_idx');
                    $table->dropColumn('estado_recepcion');
                }
            });
        }

        if (Schema::hasTable('orden_compra_detalles')) {
            Schema::table('orden_compra_detalles', function (Blueprint $table) {
                if (Schema::hasColumn('orden_compra_detalles', 'cantidad_recibida')) {
                    $table->dropColumn('cantidad_recibida');
                }
            });
        }
    }
};
