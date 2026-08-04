<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empleado_epp_entregas', function (Blueprint $table) {
            if (!Schema::hasColumn('empleado_epp_entregas', 'obra_id')) {
                $table->foreignId('obra_id')
                    ->nullable()
                    ->after('condicion')
                    ->constrained('obras')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('empleado_epp_entregas', 'area_id')) {
                $table->foreignId('area_id')
                    ->nullable()
                    ->after('obra_id')
                    ->constrained('areas')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleado_epp_entregas', function (Blueprint $table) {
            if (Schema::hasColumn('empleado_epp_entregas', 'area_id')) {
                $table->dropConstrainedForeignId('area_id');
            }

            if (Schema::hasColumn('empleado_epp_entregas', 'obra_id')) {
                $table->dropConstrainedForeignId('obra_id');
            }
        });
    }
};
