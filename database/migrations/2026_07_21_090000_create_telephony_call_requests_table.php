<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telephony_call_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('phone_extension_id')->nullable()->constrained('phone_extensions')->nullOnDelete();
            $table->foreignId('telephony_phone_number_id')->nullable()->constrained('telephony_phone_numbers')->nullOnDelete();
            $table->string('caller_extension', 30)->index();
            $table->string('outbound_number', 80);
            $table->string('normalized_outbound_number', 30)->nullable()->index();
            $table->string('phoneable_type')->nullable();
            $table->unsignedBigInteger('phoneable_id')->nullable();
            $table->string('phoneable_name')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('source', 30)->default('web')->index();
            $table->string('claimed_by_agent')->nullable()->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('ucm_status', 30)->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'telephony_call_requests_queue_index');
            $table->index(['phoneable_type', 'phoneable_id'], 'telephony_call_requests_phoneable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telephony_call_requests');
    }
};
