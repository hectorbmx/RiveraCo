<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            if (! Schema::hasColumn('huentitan_ordenes_fabricacion', 'apartada_at')) {
                $table->timestamp('apartada_at')->nullable()->after('calculada_por');
            }

            if (! Schema::hasColumn('huentitan_ordenes_fabricacion', 'apartada_por')) {
                $table->foreignId('apartada_por')->nullable()->after('apartada_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            if (! Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'cantidad_apartada')) {
                $table->decimal('cantidad_apartada', 14, 3)->default(0)->after('cantidad_sugerida_compra');
            }

            if (! Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'apartada_at')) {
                $table->timestamp('apartada_at')->nullable()->after('cantidad_apartada');
            }

            if (! Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'apartada_por')) {
                $table->foreignId('apartada_por')->nullable()->after('apartada_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            if (Schema::hasColumn('huentitan_orden_fabricacion_materiales', 'apartada_por')) {
                $table->dropConstrainedForeignId('apartada_por');
            }

            foreach (['apartada_at', 'cantidad_apartada'] as $column) {
                if (Schema::hasColumn('huentitan_orden_fabricacion_materiales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            if (Schema::hasColumn('huentitan_ordenes_fabricacion', 'apartada_por')) {
                $table->dropConstrainedForeignId('apartada_por');
            }

            if (Schema::hasColumn('huentitan_ordenes_fabricacion', 'apartada_at')) {
                $table->dropColumn('apartada_at');
            }
        });
    }
};
