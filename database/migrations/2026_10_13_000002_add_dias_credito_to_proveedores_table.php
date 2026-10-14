<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (! Schema::hasColumn('proveedores', 'dias_credito')) {
                $table->unsignedSmallInteger('dias_credito')
                    ->default(0)
                    ->after('cuenta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores', 'dias_credito')) {
                $table->dropColumn('dias_credito');
            }
        });
    }
};
