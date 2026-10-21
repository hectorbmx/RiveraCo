<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventario_documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('mantenimiento_abastecimiento_id')->nullable()->after('orden_compra_id');
            $table->index('mantenimiento_abastecimiento_id', 'inv_docs_mant_abast_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_documentos', function (Blueprint $table) {
            $table->dropIndex('inv_docs_mant_abast_idx');
            $table->dropColumn('mantenimiento_abastecimiento_id');
        });
    }
};
