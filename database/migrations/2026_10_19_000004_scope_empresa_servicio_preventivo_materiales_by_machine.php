<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_servicio_preventivo_materiales', function (Blueprint $table): void {
            $table->dropUnique('esp_materiales_tipo_producto_unique');
            $table->unique(
                ['servicio_preventivo_tipo_id', 'maquina_id', 'producto_id'],
                'esp_materiales_tipo_maquina_producto_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('empresa_servicio_preventivo_materiales', function (Blueprint $table): void {
            $table->dropUnique('esp_materiales_tipo_maquina_producto_unique');
            $table->unique(
                ['servicio_preventivo_tipo_id', 'producto_id'],
                'esp_materiales_tipo_producto_unique'
            );
        });
    }
};
