<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'es_caja_chica')) {
                $table->boolean('es_caja_chica')
                    ->default(false)
                    ->after('area_id')
                    ->index();
            }
        });

        if (Schema::hasColumn('ordenes_compra', 'proveedor_id')) {
            try {
                Schema::table('ordenes_compra', function (Blueprint $table) {
                    $table->dropForeign(['proveedor_id']);
                });
            } catch (\Throwable $e) {
                // La FK puede tener otro nombre o ya no existir en algunas bases.
            }

            try {
                DB::statement('ALTER TABLE ordenes_compra MODIFY proveedor_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // En motores que no soportan MODIFY, se deja como este en la base actual.
            }

            try {
                Schema::table('ordenes_compra', function (Blueprint $table) {
                    $table->foreign('proveedor_id')
                        ->references('id')
                        ->on('proveedores')
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                });
            } catch (\Throwable $e) {
                // Evita bloquear la migracion si la FK ya fue recreada manualmente.
            }
        }
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'es_caja_chica')) {
                $table->dropColumn('es_caja_chica');
            }
        });
    }
};