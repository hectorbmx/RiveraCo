<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_empresas', function (Blueprint $table) {
            $table->text('sat_password')->nullable()->after('fiel_password');
        });
    }

    public function down(): void
    {
        Schema::table('sat_empresas', function (Blueprint $table) {
            $table->dropColumn('sat_password');
        });
    }
};