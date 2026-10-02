<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'servicio_preventivo_tipo_id')) {
                $table->foreignId('servicio_preventivo_tipo_id')
                    ->nullable()
                    ->after('categoria_mantenimiento')
                    ->constrained('empresa_servicio_preventivo_tipos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimientos', 'servicio_preventivo_tipo_id')) {
                $table->dropConstrainedForeignId('servicio_preventivo_tipo_id');
            }
        });
    }
};