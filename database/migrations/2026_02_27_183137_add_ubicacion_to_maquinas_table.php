<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            // ubicacion física/operativa (separada de estado/condición)
            if (!Schema::hasColumn('maquinas', 'ubicacion')) {
                $table->enum('ubicacion', [
                    'en_obra',
                    'en_camino',
                    'en_reparacion',
                    'en_patio',
                ])->default('en_patio')->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            if (Schema::hasColumn('maquinas', 'ubicacion')) {
                $table->dropColumn('ubicacion');
            }
        });
    }
};