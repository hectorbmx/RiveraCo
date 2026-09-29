<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_maquina_registros', function (Blueprint $table) {
            if (! Schema::hasColumn('obra_maquina_registros', 'origen')) {
                $table->string('origen', 20)->nullable()->after('updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obra_maquina_registros', function (Blueprint $table) {
            if (Schema::hasColumn('obra_maquina_registros', 'origen')) {
                $table->dropColumn('origen');
            }
        });
    }
};
