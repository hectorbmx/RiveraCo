<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civil_estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->foreignId('civil_catalog_import_id')->constrained('civil_catalog_imports')->cascadeOnDelete();
            $table->string('folio', 80);
            $table->string('status', 30)->default('confirmed');
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_quantity', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['obra_id', 'folio']);
            $table->index(['obra_id', 'status']);
            $table->index('civil_catalog_import_id');
        });

        Schema::create('civil_estimation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civil_estimation_id')->constrained('civil_estimations')->cascadeOnDelete();
            $table->foreignId('civil_concept_id')->constrained('civil_concepts')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->json('concept_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['civil_estimation_id', 'civil_concept_id'], 'civil_estimation_concept_unique');
            $table->index('civil_concept_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civil_estimation_items');
        Schema::dropIfExists('civil_estimations');
    }
};