<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            // Totales de la obra
            $table->decimal('profundidad_total', 10, 2)->nullable()->after('monto_modificado');
            $table->decimal('kg_acero_total', 15, 2)->nullable()->after('profundidad_total');
            $table->decimal('bentonita_total', 15, 2)->nullable()->after('kg_acero_total');
            $table->decimal('concreto_total', 15, 2)->nullable()->after('bentonita_total');
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn([
                'profundidad_total',
                'kg_acero_total',
                'bentonita_total',
                'concreto_total',
            ]);
        });
    }
};
