<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            if (!Schema::hasColumn('nomina_recibos', 'subtipo')) {
                $table->string('subtipo', 50)->nullable()->after('tipo_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            if (Schema::hasColumn('nomina_recibos', 'subtipo')) {
                $table->dropColumn('subtipo');
            }
        });
    }
};
