<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_civil_material_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 255);
            $table->string('family', 100);
            $table->string('grade', 50)->nullable();
            $table->json('source_codes')->nullable();
            $table->json('keywords')->nullable();
            $table->json('match_rules')->nullable();
            $table->json('budget_units')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['family', 'grade'], 'ocmg_family_grade_idx');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_civil_material_groups');
    }
};
