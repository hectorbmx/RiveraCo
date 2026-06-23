<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            if (!Schema::hasColumn('obras', 'area_id')) {
                $table->foreignId('area_id')
                    ->nullable()
                    ->after('tipo_obra')
                    ->constrained('areas')
                    ->nullOnDelete();
            }
        });

        $pilasId = DB::table('areas')
            ->where(function ($query) {
                $query->where('codigo', 'PILAS')
                    ->orWhere('codigo', 'PI')
                    ->orWhere('nombre', 'like', '%Pilas%');
            })
            ->value('id');

        $pozosId = DB::table('areas')
            ->where(function ($query) {
                $query->where('codigo', 'POZOS')
                    ->orWhere('codigo', 'PO')
                    ->orWhere('nombre', 'like', '%Pozos%');
            })
            ->value('id');

        if ($pilasId) {
            DB::table('obras')
                ->where('tipo_obra', 'PILAS')
                ->whereNull('area_id')
                ->update(['area_id' => $pilasId]);
        }

        if ($pozosId) {
            DB::table('obras')
                ->where('tipo_obra', 'POZOS')
                ->whereNull('area_id')
                ->update(['area_id' => $pozosId]);
        }
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            if (Schema::hasColumn('obras', 'area_id')) {
                $table->dropConstrainedForeignId('area_id');
            }
        });
    }
};
