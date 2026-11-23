<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->foreignId('obra_generada_id')->nullable()->unique()->constrained('obras')->restrictOnDelete();
            $table->foreignId('version_aprobada_id')->nullable();
            $table->index(['id', 'version_aprobada_id'], 'presupuestos_version_aprobada_index');
            $table->foreign(['id', 'version_aprobada_id'], 'presupuestos_version_aprobada_fk')
                ->references(['presupuesto_id', 'id'])->on('presupuesto_versiones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign('presupuestos_version_aprobada_fk');
            $table->dropIndex('presupuestos_version_aprobada_index');
            $table->dropColumn('version_aprobada_id');
            $table->dropConstrainedForeignId('obra_generada_id');
        });
    }
};
