<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huentitan_formula_materiales', function (Blueprint $table) {
            $table->string('metodo_costo', 40)->default('promedio_inventario')->after('merma_porcentaje');
            $table->decimal('costo_unitario_override', 12, 4)->nullable()->after('metodo_costo');
        });
    }

    public function down(): void
    {
        Schema::table('huentitan_formula_materiales', function (Blueprint $table) {
            $table->dropColumn(['metodo_costo', 'costo_unitario_override']);
        });
    }
};
