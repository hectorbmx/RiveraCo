<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_open_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_device_id')->constrained('agent_devices')->cascadeOnDelete();
            $table->uuid('notification_id')->nullable()->index();
            $table->string('token_hash', 64)->unique();
            $table->text('target_url');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index(['agent_device_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_open_links');
    }
};
