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
    Schema::table('sat_document_requests', function (Blueprint $table) {
        $table->string('captcha_token', 64)->nullable()->after('captcha_answer');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sat_document_requests', function (Blueprint $table) {
            //
        });
    }
};
