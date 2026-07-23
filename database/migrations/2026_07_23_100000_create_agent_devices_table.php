<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_uuid', 100);
            $table->string('computer_name')->nullable();
            $table->foreignId('token_id')->nullable()->constrained('personal_access_tokens')->nullOnDelete();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'device_uuid']);
            $table->index(['user_id', 'revoked_at']);
            $table->index(['token_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_devices');
    }
};