<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->foreignId('version_origen_borrador_id')->nullable();
            $table->text('motivo_borrador_revision')->nullable();
            $table->index(['id', 'version_origen_borrador_id'], 'presupuestos_origen_borrador_index');
            $table->foreign(['id', 'version_origen_borrador_id'], 'presupuestos_origen_borrador_fk')
                ->references(['presupuesto_id', 'id'])->on('presupuesto_versiones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign('presupuestos_origen_borrador_fk');
            $table->dropIndex('presupuestos_origen_borrador_index');
            $table->dropColumn(['version_origen_borrador_id', 'motivo_borrador_revision']);
        });
    }
};
