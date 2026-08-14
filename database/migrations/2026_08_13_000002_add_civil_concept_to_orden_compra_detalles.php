<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->foreignId('civil_concept_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('civil_concepts')
                ->nullOnDelete();

            $table->json('civil_concept_snapshot')
                ->nullable()
                ->after('civil_concept_id');

            $table->index('civil_concept_id');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropForeign(['civil_concept_id']);
            $table->dropIndex(['civil_concept_id']);
            $table->dropColumn(['civil_concept_id', 'civil_concept_snapshot']);
        });
    }
};

