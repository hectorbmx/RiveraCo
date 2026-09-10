<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sat_facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('sat_facturas', 'timbrado_por')) {
                $table->foreignId('timbrado_por')
                    ->nullable()
                    ->after('estado')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sat_facturas', function (Blueprint $table) {
            if (Schema::hasColumn('sat_facturas', 'timbrado_por')) {
                $table->dropConstrainedForeignId('timbrado_por');
            }
        });
    }
};
