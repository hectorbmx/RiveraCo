<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nomina_pagos_extra', function (Blueprint $table) {

            // 1) Asegurar NOT NULL
            $table->unsignedBigInteger('recibo_id')->nullable(false)->change();

            // 2) UNIQUE (1 extra por recibo)
            $table->unique('recibo_id', 'nomina_pagos_extra_recibo_unique');

            // 3) FK (no crear index manual)
            $table->foreign('recibo_id', 'nomina_pagos_extra_recibo_fk')
                  ->references('id')
                  ->on('nomina_recibos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('nomina_pagos_extra', function (Blueprint $table) {
            $table->dropForeign('nomina_pagos_extra_recibo_fk');
            $table->dropUnique('nomina_pagos_extra_recibo_unique');
            $table->unsignedBigInteger('recibo_id')->nullable()->change();
        });
    }
};
