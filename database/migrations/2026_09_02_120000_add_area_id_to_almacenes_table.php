<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            if (! Schema::hasColumn('almacenes', 'area_id')) {
                $table->foreignId('area_id')
                    ->nullable()
                    ->after('obra_id')
                    ->constrained('areas')
                    ->nullOnDelete();

                $table->index('area_id', 'almacenes_area_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            if (Schema::hasColumn('almacenes', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropIndex('almacenes_area_id_index');
                $table->dropColumn('area_id');
            }
        });
    }
};
