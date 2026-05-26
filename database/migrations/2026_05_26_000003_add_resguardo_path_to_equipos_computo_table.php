<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->string('resguardo_path')->nullable()->after('factura_path');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropColumn('resguardo_path');
        });
    }
};
