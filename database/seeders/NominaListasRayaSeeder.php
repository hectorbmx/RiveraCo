<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Empleado;
use App\Models\NominaListaRaya;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NominaListasRayaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = Area::query()->get()->keyBy(fn (Area $area) => strtoupper((string) $area->codigo));

        $fixed = [
            'Oficina' => ['tipo' => NominaListaRaya::TIPO_OFICINA, 'orden' => 10, 'area_codes' => ['ADM', 'SIS']],
            'Almacen Giralda' => ['tipo' => NominaListaRaya::TIPO_ALMACEN, 'orden' => 20, 'area_codes' => ['GL']],
            'Almacen Huentitan' => ['tipo' => NominaListaRaya::TIPO_ALMACEN, 'orden' => 30, 'area_codes' => ['HT']],
            'Pilas' => ['tipo' => NominaListaRaya::TIPO_OPERATIVA, 'orden' => 40, 'area_codes' => ['PI']],
            'Pozos' => ['tipo' => NominaListaRaya::TIPO_OPERATIVA, 'orden' => 50, 'area_codes' => ['PO']],
            'Sin clasificar' => ['tipo' => NominaListaRaya::TIPO_OPERATIVA, 'orden' => 999, 'area_codes' => []],
        ];

        $areaToLista = [];

        foreach ($fixed as $nombre => $config) {
            $primaryArea = collect($config['area_codes'])
                ->map(fn (string $code) => $areas->get($code))
                ->filter()
                ->first();

            $lista = NominaListaRaya::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'tipo' => $config['tipo'],
                    'area_id' => $primaryArea?->id,
                    'obra_id' => null,
                    'almacen_id' => null,
                    'activo' => true,
                    'es_automatica' => false,
                    'orden' => $config['orden'],
                ]
            );

            foreach ($config['area_codes'] as $code) {
                if ($area = $areas->get($code)) {
                    $areaToLista[(int) $area->id] = $lista->id;
                }
            }
        }

        Area::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->each(function (Area $area) use (&$areaToLista) {
                if (isset($areaToLista[(int) $area->id])) {
                    return;
                }

                $nombre = Str::title(Str::lower($area->nombre));

                $lista = NominaListaRaya::firstOrCreate(
                    [
                        'tipo' => NominaListaRaya::TIPO_AREA,
                        'area_id' => $area->id,
                    ],
                    [
                        'nombre' => $nombre,
                        'obra_id' => null,
                        'almacen_id' => null,
                        'activo' => true,
                        'es_automatica' => false,
                        'orden' => 100,
                    ]
                );

                $areaToLista[(int) $area->id] = $lista->id;
            });

        foreach ($areaToLista as $areaId => $listaId) {
            Empleado::query()
                ->where('Area', (string) $areaId)
                ->whereNull('lista_raya_principal_id')
                ->update(['lista_raya_principal_id' => $listaId]);
        }
    }
}