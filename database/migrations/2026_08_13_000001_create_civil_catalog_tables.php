<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civil_catalog_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_path')->nullable();
            $table->string('sheet_name')->default('CATALOGO');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('total_buildings')->default(0);
            $table->unsignedInteger('total_partidas')->default(0);
            $table->unsignedInteger('total_concepts')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['obra_id', 'status']);
        });

        Schema::create('civil_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civil_catalog_import_id')->constrained('civil_catalog_imports')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('excel_row')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['civil_catalog_import_id', 'sort_order']);
        });

        Schema::create('civil_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civil_building_id')->constrained('civil_buildings')->cascadeOnDelete();
            $table->string('code', 100)->nullable();
            $table->string('name');
            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->unsignedInteger('excel_row')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['civil_building_id', 'sort_order']);
            $table->index('code');
        });

        Schema::create('civil_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civil_partida_id')->constrained('civil_partidas')->cascadeOnDelete();
            $table->string('excel_code', 100)->nullable();
            $table->text('description');
            $table->string('unit', 50)->nullable();
            $table->decimal('budget_quantity', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->string('unit_price_text')->nullable();
            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->unsignedInteger('excel_row')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['civil_partida_id', 'sort_order']);
            $table->index('excel_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civil_concepts');
        Schema::dropIfExists('civil_partidas');
        Schema::dropIfExists('civil_buildings');
        Schema::dropIfExists('civil_catalog_imports');
    }
};

