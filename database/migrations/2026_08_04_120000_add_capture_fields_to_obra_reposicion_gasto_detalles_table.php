<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_reposicion_gasto_detalles', function (Blueprint $table) {
            $table->string('comprobante_tipo', 20)->nullable()->after('monto');
            $table->string('numero_nota', 80)->nullable()->after('comprobante_tipo');
            $table->unsignedSmallInteger('dias')->nullable()->after('numero_nota');
            $table->decimal('importe_unitario', 14, 2)->nullable()->after('dias');
        });
    }

    public function down(): void
    {
        Schema::table('obra_reposicion_gasto_detalles', function (Blueprint $table) {
            $table->dropColumn([
                'comprobante_tipo',
                'numero_nota',
                'dias',
                'importe_unitario',
            ]);
        });
    }
};