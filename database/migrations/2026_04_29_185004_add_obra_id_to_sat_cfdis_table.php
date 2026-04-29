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
    Schema::table('sat_cfdis', function (Blueprint $table) {
        $table->foreignId('obra_id')
            ->nullable()
            ->after('sat_download_request_id')
            ->constrained('obras')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('sat_cfdis', function (Blueprint $table) {
        $table->dropForeign(['obra_id']);
        $table->dropColumn('obra_id');
    });
}
};
