<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalogo_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('catalogo_roles', 'activo')) {
                $table->boolean('activo')->default(true)->after('comisionable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_roles', function (Blueprint $table) {
            if (Schema::hasColumn('catalogo_roles', 'activo')) {
                $table->dropColumn('activo');
            }
        });
    }
};
