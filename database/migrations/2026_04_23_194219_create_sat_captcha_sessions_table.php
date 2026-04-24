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
    Schema::create('sat_captcha_sessions', function (Blueprint $table) {
        $table->string('token', 64)->primary();
        $table->longText('image_inline_html');   // el data:image/png;base64,...
        $table->string('answer')->nullable();
        $table->boolean('answered')->default(false);
        $table->timestamp('expires_at');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('sat_captcha_sessions');
}
};
