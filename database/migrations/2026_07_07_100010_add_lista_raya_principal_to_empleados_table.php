<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (! Schema::hasColumn('empleados', 'lista_raya_principal_id')) {
                $table->unsignedBigInteger('lista_raya_principal_id')->nullable()->after('listaraya');
                $table->index('lista_raya_principal_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (Schema::hasColumn('empleados', 'lista_raya_principal_id')) {
                $table->dropIndex(['lista_raya_principal_id']);
                $table->dropColumn('lista_raya_principal_id');
            }
        });
    }
};
