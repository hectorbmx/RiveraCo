<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mantenimientos MODIFY estatus ENUM('borrador', 'pendiente', 'en_proceso', 'completado', 'cancelado') NOT NULL DEFAULT 'borrador'");

        Schema::table('mantenimientos', function (Blueprint $table): void {
            $table->string('abastecimiento_tipo', 30)
                ->nullable()
                ->after('estatus');
            $table->string('abastecimiento_estado', 30)
                ->default('pendiente')
                ->after('abastecimiento_tipo');
            $table->index(['abastecimiento_tipo', 'abastecimiento_estado'], 'mantenimientos_abastecimiento_index');
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table): void {
            $table->dropIndex('mantenimientos_abastecimiento_index');
            $table->dropColumn(['abastecimiento_tipo', 'abastecimiento_estado']);
        });

        DB::statement("ALTER TABLE mantenimientos MODIFY estatus ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente'");
    }
};
