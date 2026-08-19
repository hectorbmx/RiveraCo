<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->foreignId('obra_civil_insumo_id')
                ->nullable()
                ->after('civil_concept_snapshot')
                ->constrained('obra_civil_insumos')
                ->nullOnDelete();

            $table->json('obra_civil_insumo_snapshot')
                ->nullable()
                ->after('obra_civil_insumo_id');

            $table->index('obra_civil_insumo_id');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropForeign(['obra_civil_insumo_id']);
            $table->dropIndex(['obra_civil_insumo_id']);
            $table->dropColumn(['obra_civil_insumo_id', 'obra_civil_insumo_snapshot']);
        });
    }
};