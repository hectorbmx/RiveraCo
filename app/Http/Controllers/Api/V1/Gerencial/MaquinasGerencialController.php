<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Maquina;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;


class MaquinasGerencialController extends Controller
{
    public function index(Request $request, \App\Services\Mobile\Equipos\MaquinasConsultaService $consulta)
    {
        return response()->json($consulta->index($request));
    }

    public function show(Request $request, Maquina $maquina, \App\Services\Mobile\Equipos\MaquinasConsultaService $consulta)
    {
        return response()->json($consulta->show($request, $maquina));
    }
//registros
public function registros(Request $request, Maquina $maquina)
{
    // 1) Determinar la asignación activa (o la última si no hay activa)
    $asignacion = ObraMaquina::query()
        ->where('maquina_id', $maquina->id)
        ->activas()
        ->latest('fecha_inicio')
        ->first();

    if (!$asignacion) {
        $asignacion = ObraMaquina::query()
            ->where('maquina_id', $maquina->id)
            ->latest('fecha_inicio')
            ->first();
    }

    if (!$asignacion) {
        return response()->json([
            'ok' => true,
            'data' => [
                'maquina' => [
                    'id' => (int) $maquina->id,
                    'nombre' => $maquina->nombre ?? null,
                ],
                'asignacion' => null,
                'registros' => [],
                'meta' => [
                    'total' => 0,
                ],
            ],
        ]);
    }

    // 2) Query de registros (paginado + filtros por fecha opcionales)
    $rq = ObraMaquinaRegistro::query()
        ->where('obra_maquina_id', $asignacion->id)
        ->orderByDesc('fin')
        ->orderByDesc('id');

    if ($request->filled('from')) {
        $rq->whereDate('fin', '>=', $request->from);
    }
    if ($request->filled('to')) {
        $rq->whereDate('fin', '<=', $request->to);
    }

    $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
    $rows = $rq->paginate($perPage)->withQueryString();

    // 3) Respuesta
    return response()->json([
        'ok' => true,
        'data' => [
            'maquina' => [
                'id' => (int) $maquina->id,
                'nombre' => $maquina->nombre ?? null,
            ],
            'asignacion' => [
                'obra_maquina_id' => (int) $asignacion->id,
                'obra_id' => (int) $asignacion->obra_id,
                'fecha_inicio' => optional($asignacion->fecha_inicio)->toDateString(),
                'fecha_fin' => optional($asignacion->fecha_fin)->toDateString(),
                'estado' => $asignacion->estado,
                'horometro_inicio' => $asignacion->horometro_inicio,
            ],
            'registros' => $rows->through(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'inicio' => optional($r->inicio)->toDateTimeString(),
                    'fin' => optional($r->fin)->toDateTimeString(),
                    'horometro_inicio' => $r->horometro_inicio,
                    'horometro_fin' => $r->horometro_fin,
                    'horas' => $r->horas,
                    'notas' => $r->notas,
                    'created_by' => $r->created_by,
                    'created_at' => optional($r->created_at)->toDateTimeString(),
                ];
            }),
        ],
        'meta' => [
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(),
            'total' => $rows->total(),
        ],
    ]);
}
public function registrosResumen(Request $request, Maquina $maquina)
{
    // rango opcional (por defecto últimos 30 días)
    $days = (int) $request->get('days', 30);
    $days = max(1, min($days, 365));
    $from = now()->subDays($days)->startOfDay();

    // Asignación activa (si existe)
    $asignacionActiva = ObraMaquina::query()
        ->where('maquina_id', $maquina->id)
        ->activas()
        ->latest('fecha_inicio')
        ->first();

    // Última asignación (por si no hay activa)
    $ultimaAsignacion = $asignacionActiva ?: ObraMaquina::query()
        ->where('maquina_id', $maquina->id)
        ->latest('fecha_inicio')
        ->first();

    // Base query: registros de la máquina (sin importar asignación) para resumen global
    $base = ObraMaquinaRegistro::query()
        ->where('maquina_id', $maquina->id);

    // Totales globales
    $totalHoras = (float) $base->sum('horas');
    $totalRegistros = (int) $base->count();

    $ultimoRegistro = ObraMaquinaRegistro::query()
        ->where('maquina_id', $maquina->id)
        ->orderByDesc('fin')
        ->orderByDesc('id')
        ->first();

    // Ventana últimos N días (global)
    $baseWindow = ObraMaquinaRegistro::query()
        ->where('maquina_id', $maquina->id)
        ->where('fin', '>=', $from);

    $horasWindow = (float) $baseWindow->sum('horas');
    $registrosWindow = (int) $baseWindow->count();

    // Ventana últimos N días SOLO asignación activa (si existe)
    $horasActivaWindow = null;
    $registrosActivaWindow = null;

    if ($asignacionActiva) {
        $qActiva = ObraMaquinaRegistro::query()
            ->where('obra_maquina_id', $asignacionActiva->id)
            ->where('fin', '>=', $from);

        $horasActivaWindow = (float) $qActiva->sum('horas');
        $registrosActivaWindow = (int) $qActiva->count();
    }

    return response()->json([
        'ok' => true,
        'data' => [
            'maquina' => [
                'id' => (int) $maquina->id,
                'nombre' => $maquina->nombre ?? null,
            ],
            'asignacion_activa' => $asignacionActiva ? [
                'obra_maquina_id' => (int) $asignacionActiva->id,
                'obra_id' => (int) $asignacionActiva->obra_id,
                'estado' => $asignacionActiva->estado,
                'fecha_inicio' => optional($asignacionActiva->fecha_inicio)->toDateString(),
                'horometro_inicio' => $asignacionActiva->horometro_inicio,
            ] : null,
            'ultima_asignacion' => $ultimaAsignacion ? [
                'obra_maquina_id' => (int) $ultimaAsignacion->id,
                'obra_id' => (int) $ultimaAsignacion->obra_id,
                'estado' => $ultimaAsignacion->estado,
                'fecha_inicio' => optional($ultimaAsignacion->fecha_inicio)->toDateString(),
                'fecha_fin' => optional($ultimaAsignacion->fecha_fin)->toDateString(),
            ] : null,
            'kpis' => [
                'total_registros' => $totalRegistros,
                'total_horas' => round($totalHoras, 2),

                'window' => [
                    'days' => $days,
                    'from' => $from->toDateString(),
                    'registros' => $registrosWindow,
                    'horas' => round($horasWindow, 2),
                    'registros_asignacion_activa' => $registrosActivaWindow,
                    'horas_asignacion_activa' => $horasActivaWindow !== null ? round($horasActivaWindow, 2) : null,
                ],

                'ultimo_registro' => $ultimoRegistro ? [
                    'id' => (int) $ultimoRegistro->id,
                    'fin' => optional($ultimoRegistro->fin)->toDateTimeString(),
                    'horometro_fin' => $ultimoRegistro->horometro_fin,
                    'horas' => $ultimoRegistro->horas,
                    'obra_id' => $ultimoRegistro->obra_id,
                    'obra_maquina_id' => $ultimoRegistro->obra_maquina_id,
                ] : null,
            ],
        ],
    ]);
}

}
