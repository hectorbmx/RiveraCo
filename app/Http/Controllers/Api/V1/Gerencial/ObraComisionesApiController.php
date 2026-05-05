<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\Comision;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ObraComisionesApiController extends Controller
{
    public function index(Request $request, Obra $obra)
    {
        $selectedFecha = $request->query('fecha');

        $fechasDisponibles = Comision::where('obra_id', $obra->id)
            ->select('fecha')
            ->distinct()
            ->orderByDesc('fecha')
            ->pluck('fecha')
            ->map(fn ($fecha) => Carbon::parse($fecha)->format('Y-m-d'))
            ->values();

       $query = Comision::where('obra_id', $obra->id)
            ->with([
                'pila',
                'detalles.asignacionMaquina.maquina',
                'perforaciones',
            ])
            ->withSum('detalles as total_pilas', 'cantidad')
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($selectedFecha) {
            $query->whereDate('fecha', $selectedFecha);
        }

        $comisiones = $query->get();

        $agrupadas = $comisiones
            ->groupBy(fn ($comision) => $comision->fecha?->format('Y-m-d'))
            ->map(function ($items, $fecha) {
                return [
                    'fecha' => $fecha,
                    'fecha_formateada' => Carbon::parse($fecha)->format('d/m/Y'),
                    'total_pilas' => (float) $items->sum('total_pilas'),
                    'comisiones_registradas' => $items->count(),
                    'comisiones' => $items->map(function ($comision) {
                        $perforaciones = $comision->perforaciones
                        ->sortBy('id')
                        ->values();
                        return [
                            'id' => $comision->id,
                            'fecha' => $comision->fecha?->format('Y-m-d'),
                            'total_pilas' => (float) ($comision->total_pilas ?? 0),

                            'pila' => $comision->pila ? [
                                'id' => $comision->pila->id,
                                'numero_pila' => $comision->pila->numero_pila ?? null,
                                'diametro_cm' => $comision->pila->diametro_cm ?? null,
                                'profundidad_m' => $comision->pila->profundidad_m ?? null,
                            ] : null,

                            'detalles' => $comision->detalles->values()->map(function ($detalle, $i) use ($perforaciones) {
                                $perf = $perforaciones[$i] ?? null;

                                return [
                                    'id' => $detalle->id,

                                    'hora_inicio' => $perf?->hora_inicio,
                                    'hora_fin' => $perf?->hora_termino,

                                    'cantidad' => (float) $detalle->cantidad,
                                    'profundidad' => (float) ($detalle->profundidad ?? 0),
                                    'metros_sujetos_comision' => (float) ($detalle->metros_sujetos_comision ?? 0),
                                    'kg_acero' => (float) ($detalle->kg_acero ?? 0),
                                    'vol_bentonita' => (float) ($detalle->vol_bentonita ?? 0),
                                    'vol_concreto' => (float) ($detalle->vol_concreto ?? 0),

                                    'maquina' => $detalle->asignacionMaquina?->maquina ? [
                                        'id' => $detalle->asignacionMaquina->maquina->id,
                                        'nombre' => $detalle->asignacionMaquina->maquina->nombre,
                                    ] : null,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'obra' => [
                    'id' => $obra->id,
                    'nombre' => $obra->nombre ?? $obra->Nombre ?? null,
                ],
                'fechas_disponibles' => $fechasDisponibles,
                'selected_fecha' => $selectedFecha,
                'total_realizadas' => (float) $comisiones->sum('total_pilas'),
                'rows' => $agrupadas,
            ],
        ]);
    }
}