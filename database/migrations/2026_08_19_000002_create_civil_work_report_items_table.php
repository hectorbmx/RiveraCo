<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civil_work_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civil_work_report_id')->constrained('civil_work_reports')->cascadeOnDelete();
            $table->foreignId('civil_concept_id')->constrained('civil_concepts')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('unit', 50)->nullable();
            $table->json('concept_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('civil_concept_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civil_work_report_items');
    }
};
