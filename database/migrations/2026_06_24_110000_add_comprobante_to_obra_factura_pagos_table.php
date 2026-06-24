<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('obra_factura_pagos', 'comprobante_path')) {
                $table->string('comprobante_path')->nullable()->after('observaciones');
            }

            if (!Schema::hasColumn('obra_factura_pagos', 'comprobante_nombre_original')) {
                $table->string('comprobante_nombre_original')->nullable()->after('comprobante_path');
            }

            if (!Schema::hasColumn('obra_factura_pagos', 'comprobante_mime')) {
                $table->string('comprobante_mime', 120)->nullable()->after('comprobante_nombre_original');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obra_factura_pagos', function (Blueprint $table) {
            $columns = [
                'comprobante_mime',
                'comprobante_nombre_original',
                'comprobante_path',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('obra_factura_pagos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
