<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nomina_pagos_extra', function (Blueprint $table) {
            if (!Schema::hasColumn('nomina_pagos_extra', 'recibo_id')) {
                $table->unsignedBigInteger('recibo_id')->nullable()->after('id');
                $table->index('recibo_id');
            }

            // tipo: percepcion | deduccion
            if (!Schema::hasColumn('nomina_pagos_extra', 'tipo')) {
                $table->string('tipo', 20)->default('percepcion')->after('obra_id');
            }

            // Si quieres que empleado_id sea redundante pero útil, lo dejas como está.
            // Si quieres obligar relación hija-real, más adelante haces recibo_id NOT NULL.
        });

        // FK opcional: si quieres amarrar items a su recibo
        Schema::table('nomina_pagos_extra', function (Blueprint $table) {
            // Recomendado cuando ya estés listo:
            // $table->foreign('recibo_id')->references('id')->on('nomina_recibos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nomina_pagos_extra', function (Blueprint $table) {
            // Si activaste FK, descomenta:
            // try { $table->dropForeign(['recibo_id']); } catch (\Throwable $e) {}

            if (Schema::hasColumn('nomina_pagos_extra', 'recibo_id')) {
                $table->dropColumn('recibo_id');
            }
            if (Schema::hasColumn('nomina_pagos_extra', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
