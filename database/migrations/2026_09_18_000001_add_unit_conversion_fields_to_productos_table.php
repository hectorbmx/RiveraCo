<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'unidad_compra')) {
                $table->string('unidad_compra', 50)->nullable()->after('unidad');
            }

            if (! Schema::hasColumn('productos', 'cantidad_por_unidad_compra')) {
                $table->decimal('cantidad_por_unidad_compra', 14, 6)->default(1)->after('unidad_compra');
            }

            if (! Schema::hasColumn('productos', 'unidad_base')) {
                $table->string('unidad_base', 50)->nullable()->after('cantidad_por_unidad_compra');
            }
        });

        DB::table('productos')->update([
            'unidad_compra' => DB::raw('unidad'),
            'cantidad_por_unidad_compra' => 1,
            'unidad_base' => DB::raw('unidad'),
        ]);

        $this->normalizarUnidadesSimples();
        $this->normalizarEmpaquesHuentitan();
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'unidad_base')) {
                $table->dropColumn('unidad_base');
            }

            if (Schema::hasColumn('productos', 'cantidad_por_unidad_compra')) {
                $table->dropColumn('cantidad_por_unidad_compra');
            }

            if (Schema::hasColumn('productos', 'unidad_compra')) {
                $table->dropColumn('unidad_compra');
            }
        });
    }

    private function normalizarUnidadesSimples(): void
    {
        $map = [
            'PZA' => ['PZA', 1, 'PZA'],
            'PIEZA' => ['PZA', 1, 'PZA'],
            'PZA.' => ['PZA', 1, 'PZA'],
            'KG' => ['KG', 1, 'KG'],
            'KGS' => ['KG', 1, 'KG'],
            'KG.' => ['KG', 1, 'KG'],
            'LT' => ['LT', 1, 'LT'],
            'LTS' => ['LT', 1, 'LT'],
            'LTS.' => ['LT', 1, 'LT'],
            'MTS' => ['ML', 1, 'ML'],
            'MTS.' => ['ML', 1, 'ML'],
            'MT' => ['ML', 1, 'ML'],
            'MT.' => ['ML', 1, 'ML'],
            'M3' => ['M3', 1, 'M3'],
            'PAR' => ['PAR', 1, 'PAR'],
            'PARES' => ['PAR', 1, 'PAR'],
            'JGO' => ['JGO', 1, 'JGO'],
            'JGO.' => ['JGO', 1, 'JGO'],
            'KIT' => ['KIT', 1, 'KIT'],
        ];

        foreach ($map as $legacy => [$compra, $cantidad, $base]) {
            DB::table('productos')
                ->whereRaw('UPPER(TRIM(unidad)) = ?', [$legacy])
                ->update([
                    'unidad_compra' => $compra,
                    'cantidad_por_unidad_compra' => $cantidad,
                    'unidad_base' => $base,
                ]);
        }
    }

    private function normalizarEmpaquesHuentitan(): void
    {
        $porSku = [
            'HUE-0001' => ['SACO', 25, 'KG'],
            'HUE-0003' => ['ROLLO', 100, 'M2'],
            'HUE-0004' => ['ROLLO', 100, 'M2'],
            'HUE-0007' => ['ROLLO', 250, 'M2'],
            'HUE-0008' => ['ROLLO', 600, 'M2'],
            'HUE-0009' => ['CUBETA', 19, 'LT'],
            'HUE-0010' => ['CUBETA', 19, 'LT'],
            'HUE-0013' => ['BOLSA', 1, 'KG'],
            'HUE-0016' => ['BOLSA', 0.25, 'KG'],
            'HUE-0024' => ['TRAMO', 12, 'ML'],
            'HUE-0025' => ['TRAMO', 6, 'ML'],
            'HUE-0026' => ['TRAMO', 12, 'ML'],
            'HUE-0027' => ['TRAMO', 6, 'ML'],
            'HUE-0055' => ['TRAMO', 6, 'ML'],
            'HUE-0056' => ['TRAMO', 6, 'ML'],
            'HUE-0057' => ['TRAMO', 6, 'ML'],
        ];

        foreach ($porSku as $sku => [$compra, $cantidad, $base]) {
            DB::table('productos')
                ->where('sku', $sku)
                ->update([
                    'unidad_compra' => $compra,
                    'cantidad_por_unidad_compra' => $cantidad,
                    'unidad_base' => $base,
                ]);
        }
    }
};
