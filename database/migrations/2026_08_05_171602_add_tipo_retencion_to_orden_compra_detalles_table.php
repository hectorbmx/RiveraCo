<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->foreignId('tipo_retencion_id')
                ->nullable()
                ->after('iva')
                ->constrained('tipos_retencion')
                ->nullOnDelete();

            $table->decimal('retencion_porcentaje', 8, 4)
                ->default(0)
                ->after('tipo_retencion_id');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropForeign(['tipo_retencion_id']);
            $table->dropColumn([
                'tipo_retencion_id',
                'retencion_porcentaje',
            ]);
        });
    }
};