<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('sat_cfdis', function (Blueprint $table) {
            $table->foreignId('orden_compra_id')
                ->nullable()
                ->after('obra_id')
                ->constrained('ordenes_compra')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sat_cfdis', function (Blueprint $table) {
            //
        });
    }
};
