<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            $table->boolean('requiere_compra')->default(false)->after('faltante_calculado');
            $table->decimal('cantidad_sugerida_compra', 14, 3)->default(0)->after('requiere_compra');
            $table->timestamp('compra_marcada_at')->nullable()->after('cantidad_sugerida_compra');
            $table->foreignId('compra_marcada_por')->nullable()->after('compra_marcada_at');
            $table->foreign('compra_marcada_por', 'hofm_compra_marcada_por_fk')->references('id')->on('users')->nullOnDelete();
            $table->index('requiere_compra', 'hofm_requiere_compra_index');
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_orden_fabricacion_materiales', function (Blueprint $table) {
            $table->dropForeign('hofm_compra_marcada_por_fk');
            $table->dropIndex('hofm_requiere_compra_index');
            $table->dropColumn([
                'requiere_compra',
                'cantidad_sugerida_compra',
                'compra_marcada_at',
                'compra_marcada_por',
            ]);
        });
    }
};
