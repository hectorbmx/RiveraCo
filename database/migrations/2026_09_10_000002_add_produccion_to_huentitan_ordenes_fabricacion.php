<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            if (! Schema::hasColumn('huentitan_ordenes_fabricacion', 'produccion_iniciada_at')) {
                $table->timestamp('produccion_iniciada_at')->nullable()->after('apartada_por');
            }

            if (! Schema::hasColumn('huentitan_ordenes_fabricacion', 'produccion_iniciada_por')) {
                $table->foreignId('produccion_iniciada_por')->nullable()->after('produccion_iniciada_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            if (! Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'cantidad_consumida')) {
                $table->decimal('cantidad_consumida', 14, 3)->default(0)->after('apartada_por');
            }

            if (! Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'consumida_at')) {
                $table->timestamp('consumida_at')->nullable()->after('cantidad_consumida');
            }

            if (! Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'consumida_por')) {
                $table->foreignId('consumida_por')->nullable()->after('consumida_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            if (Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'consumida_por')) {
                $table->dropConstrainedForeignId('consumida_por');
            }

            foreach (['consumida_at', 'cantidad_consumida'] as $column) {
                if (Schema::hasColumn('huentitan_orden_fabricacion_materiales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            if (Schema::hasColumn('huentitan_ordenes_fabricacion', 'produccion_iniciada_por')) {
                $table->dropConstrainedForeignId('produccion_iniciada_por');
            }

            if (Schema::hasColumn('huentitan_ordenes_fabricacion', 'produccion_iniciada_at')) {
                $table->dropColumn('produccion_iniciada_at');
            }
        });
    }
};
