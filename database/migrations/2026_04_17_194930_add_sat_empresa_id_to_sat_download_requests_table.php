<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_download_requests', function (Blueprint $table) {
            $table->foreignId('sat_empresa_id')
                ->nullable()
                ->after('user_id')
                ->constrained('sat_empresas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sat_download_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sat_empresa_id');
        });
    }
};