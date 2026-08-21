<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civil_work_report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civil_work_report_item_id')->constrained('civil_work_report_items')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('civil_work_report_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civil_work_report_photos');
    }
};
