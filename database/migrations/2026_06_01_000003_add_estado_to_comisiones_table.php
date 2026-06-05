<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            if (!Schema::hasColumn('comisiones', 'estado')) {
                // Compatibilidad: las comisiones creadas por el flujo actual quedan como cerradas.
                $table->string('estado', 40)
                    ->default('cerrada')
                    ->after('fecha')
                    ->index();
            }

            if (!Schema::hasColumn('comisiones', 'cerrada_at')) {
                $table->timestamp('cerrada_at')
                    ->nullable()
                    ->after('estado');
            }

            if (!Schema::hasColumn('comisiones', 'cancelada_at')) {
                $table->timestamp('cancelada_at')
                    ->nullable()
                    ->after('cerrada_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            if (Schema::hasColumn('comisiones', 'cancelada_at')) {
                $table->dropColumn('cancelada_at');
            }

            if (Schema::hasColumn('comisiones', 'cerrada_at')) {
                $table->dropColumn('cerrada_at');
            }

            if (Schema::hasColumn('comisiones', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
