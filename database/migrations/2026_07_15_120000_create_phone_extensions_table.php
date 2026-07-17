<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('extension', 20)->unique();
            $table->string('account_type', 50)->nullable();
            $table->string('fullname')->nullable();
            $table->string('user_name')->nullable();
            $table->string('email')->nullable();
            $table->string('status', 50)->nullable()->index();
            $table->string('addr')->nullable();
            $table->boolean('out_of_service')->default(false)->index();
            $table->boolean('enable_contact')->default(false);
            $table->boolean('email_to_user')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'extension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_extensions');
    }
};
