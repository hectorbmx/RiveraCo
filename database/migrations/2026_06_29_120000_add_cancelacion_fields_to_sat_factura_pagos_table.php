<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_factura_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('sat_factura_pagos', 'fecha_cancelacion')) {
                $table->timestamp('fecha_cancelacion')->nullable()->after('estado');
            }

            if (!Schema::hasColumn('sat_factura_pagos', 'cancelado_por')) {
                $table->foreignId('cancelado_por')
                    ->nullable()
                    ->after('fecha_cancelacion')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('sat_factura_pagos', 'motivo_cancelacion')) {
                $table->string('motivo_cancelacion', 5)->nullable()->after('cancelado_por');
            }

            if (!Schema::hasColumn('sat_factura_pagos', 'sustitucion_uuid')) {
                $table->string('sustitucion_uuid', 80)->nullable()->after('motivo_cancelacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sat_factura_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('sat_factura_pagos', 'cancelado_por')) {
                $table->dropForeign(['cancelado_por']);
            }

            foreach ([
                'sustitucion_uuid',
                'motivo_cancelacion',
                'cancelado_por',
                'fecha_cancelacion',
            ] as $column) {
                if (Schema::hasColumn('sat_factura_pagos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
