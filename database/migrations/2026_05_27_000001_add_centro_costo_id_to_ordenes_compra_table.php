<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'centro_costo_id')) {
                $table->foreignId('centro_costo_id')
                    ->nullable()
                    ->after('obra_id')
                    ->constrained('centros_costo')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'centro_costo_id')) {
                $table->dropForeign(['centro_costo_id']);
                $table->dropColumn('centro_costo_id');
            }
        });
    }
};
