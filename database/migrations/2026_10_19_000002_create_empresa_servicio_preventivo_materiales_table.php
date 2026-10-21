<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empresa_servicio_preventivo_materiales')) {
            Schema::table('empresa_servicio_preventivo_materiales', function (Blueprint $table) {
                $table->foreign(
                    'servicio_preventivo_tipo_id',
                    'esp_materiales_tipo_fk'
                )->references('id')
                    ->on('empresa_servicio_preventivo_tipos')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign(
                    'producto_id',
                    'esp_materiales_producto_fk'
                )->references('id')
                    ->on('productos')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->unique(
                    ['servicio_preventivo_tipo_id', 'producto_id'],
                    'esp_materiales_tipo_producto_unique'
                );
                $table->index(
                    ['servicio_preventivo_tipo_id', 'activo', 'orden'],
                    'esp_materiales_tipo_activo_orden_index'
                );
            });

            return;
        }

        Schema::create('empresa_servicio_preventivo_materiales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('servicio_preventivo_tipo_id');
            $table->unsignedBigInteger('producto_id');
            $table->decimal('cantidad_requerida', 14, 3);
            $table->string('unidad', 50)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign(
                'servicio_preventivo_tipo_id',
                'esp_materiales_tipo_fk'
            )->references('id')
                ->on('empresa_servicio_preventivo_tipos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign(
                'producto_id',
                'esp_materiales_producto_fk'
            )->references('id')
                ->on('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['servicio_preventivo_tipo_id', 'producto_id'],
                'esp_materiales_tipo_producto_unique'
            );
            $table->index(
                ['servicio_preventivo_tipo_id', 'activo', 'orden'],
                'esp_materiales_tipo_activo_orden_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_servicio_preventivo_materiales');
    }
};
