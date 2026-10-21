<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('empresa_servicio_preventivo_materiales', 'maquina_id')) {
            Schema::table('empresa_servicio_preventivo_materiales', function (Blueprint $table): void {
                $table->unsignedBigInteger('maquina_id')->nullable()->after('servicio_preventivo_tipo_id');
                $table->foreign('maquina_id', 'esp_materiales_maquina_fk')
                    ->references('id')
                    ->on('maquinas')
                    ->nullOnDelete();
                $table->index(
                    ['maquina_id', 'servicio_preventivo_tipo_id'],
                    'esp_materiales_maquina_tipo_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('empresa_servicio_preventivo_materiales', 'maquina_id')) {
            Schema::table('empresa_servicio_preventivo_materiales', function (Blueprint $table): void {
                $table->dropForeign('esp_materiales_maquina_fk');
                $table->dropIndex('esp_materiales_maquina_tipo_index');
                $table->dropColumn('maquina_id');
            });
        }
    }
};
