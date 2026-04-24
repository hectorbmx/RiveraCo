<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('sat_document_requests', function (Blueprint $table) {
    $table->id();

    $table->foreignId('sat_empresa_id')
        ->constrained('sat_empresas')
        ->cascadeOnDelete();

    $table->string('type', 50);
    $table->string('status', 30)->default('pending');

    $table->string('file_path')->nullable();
    $table->string('file_name')->nullable();
    $table->string('mime_type', 100)->nullable();
    $table->unsignedBigInteger('file_size')->nullable();

    $table->text('error_message')->nullable();

    $table->foreignId('requested_by')->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('processed_at')->nullable();

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_document_requests');
    }
};