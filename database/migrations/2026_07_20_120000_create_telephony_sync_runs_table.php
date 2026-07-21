<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telephony_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->index();
            $table->string('source', 50)->default('agent')->index();
            $table->string('status', 30)->default('success')->index();
            $table->string('agent_computer_name')->nullable()->index();
            $table->string('agent_module', 50)->nullable();
            $table->timestamp('window_from')->nullable()->index();
            $table->timestamp('window_to')->nullable()->index();
            $table->string('window_timezone', 80)->nullable();
            $table->unsignedInteger('received')->default(0);
            $table->unsignedInteger('mapped')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['module', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telephony_sync_runs');
    }
};