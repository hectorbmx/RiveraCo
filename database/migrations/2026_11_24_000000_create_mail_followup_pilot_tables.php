<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_followups', function (Blueprint $table) {
            $table->id();
            $table->char('thread_key', 64)->unique();
            $table->string('mailbox');
            $table->string('client_name');
            $table->string('subject');
            $table->string('status', 24);
            $table->string('classification_source', 24);
            $table->string('classification_reason', 500);
            $table->string('classification_evidence', 500)->nullable();
            $table->dateTime('last_activity_at');
            $table->dateTime('status_at');
            $table->timestamps();
            $table->index(['mailbox', 'last_activity_at']);
        });

        Schema::create('mail_followup_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_followup_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('message_key', 64)->unique();
            $table->string('direction', 10);
            $table->dateTime('occurred_at');
            $table->string('author')->nullable();
            $table->string('subject')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->text('web_link')->nullable();
            $table->boolean('automatic')->default(false);
            $table->index(['mail_followup_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_followup_events');
        Schema::dropIfExists('mail_followups');
    }
};
