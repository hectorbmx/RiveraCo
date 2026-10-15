<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_documento_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario_documento_detalles', 'vehiculo_id')) {
                $table->unsignedBigInteger('vehiculo_id')->nullable()->after('producto_id');
            }
            if (!Schema::hasColumn('inventario_documento_detalles', 'maquina_id')) {
                $table->unsignedBigInteger('maquina_id')->nullable()->after('vehiculo_id');
            }
            if (!Schema::hasColumn('inventario_documento_detalles', 'equipo_tipo')) {
                $table->string('equipo_tipo', 20)->nullable()->after('maquina_id');
            }
            if (!Schema::hasColumn('inventario_documento_detalles', 'equipo_nombre_snapshot')) {
                $table->string('equipo_nombre_snapshot', 180)->nullable()->after('equipo_tipo');
            }
            if (!Schema::hasColumn('inventario_documento_detalles', 'equipo_identificador_snapshot')) {
                $table->string('equipo_identificador_snapshot', 120)->nullable()->after('equipo_nombre_snapshot');
            }
        });

        $indexes = collect(Schema::getIndexes('inventario_documento_detalles'))->pluck('name');
        Schema::table('inventario_documento_detalles', function (Blueprint $table) use ($indexes) {
            if (!$indexes->contains('inv_doc_detalles_vehiculo_idx')) {
                $table->index('vehiculo_id', 'inv_doc_detalles_vehiculo_idx');
            }
            if (!$indexes->contains('inv_doc_detalles_maquina_idx')) {
                $table->index('maquina_id', 'inv_doc_detalles_maquina_idx');
            }
            if (!$indexes->contains('inv_doc_detalles_equipo_idx')) {
                $table->index(['equipo_tipo', 'equipo_identificador_snapshot'], 'inv_doc_detalles_equipo_idx');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('inventario_documento_detalles'))->pluck('name');
        Schema::table('inventario_documento_detalles', function (Blueprint $table) use ($indexes) {
            foreach ([
                'inv_doc_detalles_equipo_idx',
                'inv_doc_detalles_vehiculo_idx',
                'inv_doc_detalles_maquina_idx',
                'inventario_documento_detalles_vehiculo_id_index',
                'inventario_documento_detalles_maquina_id_index',
            ] as $index) {
                if ($indexes->contains($index)) {
                    $table->dropIndex($index);
                }
            }

            $table->dropColumn([
                'vehiculo_id',
                'maquina_id',
                'equipo_tipo',
                'equipo_nombre_snapshot',
                'equipo_identificador_snapshot',
            ]);
        });
    }
};