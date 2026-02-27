<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {

            // Soporte para maquinaria
            if (!Schema::hasColumn('mantenimientos', 'maquina_id')) {
                $table->foreignId('maquina_id')
                    ->nullable()
                    ->after('vehiculo_id')
                    ->constrained('maquinas')
                    ->nullOnDelete();
            }

            // Horómetro (equivalente a km_actuales pero para máquinas)
            if (!Schema::hasColumn('mantenimientos', 'horometro')) {
                $table->decimal('horometro', 10, 1)
                    ->nullable()
                    ->after('km_actuales');
            }

        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {

            if (Schema::hasColumn('mantenimientos', 'maquina_id')) {
                $table->dropForeign(['maquina_id']);
                $table->dropColumn('maquina_id');
            }

            if (Schema::hasColumn('mantenimientos', 'horometro')) {
                $table->dropColumn('horometro');
            }
        });
    }
};