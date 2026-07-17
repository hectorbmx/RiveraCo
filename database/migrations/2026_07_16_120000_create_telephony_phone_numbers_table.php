<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telephony_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->morphs('phoneable');
            $table->string('source_table', 80)->nullable()->index();
            $table->string('source_column', 80)->index();
            $table->string('label', 80)->nullable();
            $table->string('raw_number', 80);
            $table->string('normalized_number', 30)->index();
            $table->string('display_name')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['phoneable_type', 'phoneable_id', 'source_column', 'normalized_number'], 'telephony_phone_unique_source');
            $table->index(['normalized_number', 'phoneable_type'], 'telephony_phone_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telephony_phone_numbers');
    }
};