<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            if (! Schema::hasColumn('nomina_recibos', 'lista_raya_id')) {
                $table->unsignedBigInteger('lista_raya_id')->nullable()->after('obra_id');
                $table->index('lista_raya_id');
            }
            if (! Schema::hasColumn('nomina_recibos', 'lista_raya_nombre')) {
                $table->string('lista_raya_nombre', 150)->nullable()->after('lista_raya_id');
            }
            if (! Schema::hasColumn('nomina_recibos', 'lista_raya_tipo')) {
                $table->string('lista_raya_tipo', 30)->nullable()->after('lista_raya_nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            if (Schema::hasColumn('nomina_recibos', 'lista_raya_id')) {
                $table->dropIndex(['lista_raya_id']);
                $table->dropColumn('lista_raya_id');
            }
            if (Schema::hasColumn('nomina_recibos', 'lista_raya_nombre')) {
                $table->dropColumn('lista_raya_nombre');
            }
            if (Schema::hasColumn('nomina_recibos', 'lista_raya_tipo')) {
                $table->dropColumn('lista_raya_tipo');
            }
        });
    }
};
