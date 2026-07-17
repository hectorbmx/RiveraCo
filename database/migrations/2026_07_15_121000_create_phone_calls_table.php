<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_calls', function (Blueprint $table) {
            $table->id();
            $table->string('ucm_cdr_id', 50)->unique();
            $table->foreignId('phone_extension_id')->nullable()->constrained('phone_extensions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session')->nullable()->index();
            $table->string('acct_id', 50)->nullable()->index();
            $table->string('uniqueid', 80)->nullable()->index();
            $table->string('action_type', 50)->nullable()->index();
            $table->string('action_owner', 80)->nullable();
            $table->string('direction', 30)->nullable()->index();
            $table->string('status', 30)->nullable()->index();
            $table->string('disposition', 50)->nullable()->index();
            $table->string('ucm_userfield', 50)->nullable()->index();
            $table->string('source_number', 80)->nullable()->index();
            $table->string('destination_number', 80)->nullable()->index();
            $table->string('source_extension', 30)->nullable()->index();
            $table->string('destination_extension', 30)->nullable()->index();
            $table->string('answered_by', 80)->nullable();
            $table->string('caller_name')->nullable();
            $table->string('clid')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('billsec')->default(0);
            $table->string('source_trunk_name')->nullable();
            $table->string('destination_trunk_name')->nullable();
            $table->string('channel')->nullable();
            $table->string('destination_channel')->nullable();
            $table->string('lastapp')->nullable();
            $table->text('lastdata')->nullable();
            $table->string('device_info')->nullable();
            $table->string('device_info_peer')->nullable();
            $table->string('recordfiles')->nullable();
            $table->string('reason')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('imported_at')->nullable()->index();
            $table->timestamps();

            $table->index(['started_at', 'direction']);
            $table->index(['source_extension', 'started_at']);
            $table->index(['destination_extension', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_calls');
    }
};
