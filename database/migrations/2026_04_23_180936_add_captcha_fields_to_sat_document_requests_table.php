<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_document_requests', function (Blueprint $table) {
            $table->string('captcha_path')->nullable()->after('file_size');
            $table->text('captcha_answer')->nullable()->after('captcha_path');
            $table->timestamp('captcha_requested_at')->nullable()->after('captcha_answer');
        });
    }

    public function down(): void
    {
        Schema::table('sat_document_requests', function (Blueprint $table) {
            $table->dropColumn([
                'captcha_path',
                'captcha_answer',
                'captcha_requested_at',
            ]);
        });
    }
};