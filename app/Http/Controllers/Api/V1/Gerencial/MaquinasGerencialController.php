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
    public function index(Request $request)
    {
        $q = Maquina::query()
            ->select([
                'id',
                'nombre',
                'placas',
                'horometro_base',
                'estado',
                'modelo',
                'tipo',

                // agrega aquí campos reales si existen: economico, marca, modelo, estatus, horometro_actual, etc.
                // 'economico','marca','modelo','estatus'
            ])
            // Relación sugerida: asignacionActiva -> ObraMaquina (whereNull fecha_fin)
            ->with([
                'asignacionActiva.obra:id,nombre,clave_obra,estatus_nuevo',
            ])
            ->orderBy('nombre');

        // filtros básicos
        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($x) use ($term) {
                $x->where('nombre', 'like', "%{$term}%");
                // si tienes economico/serie/modelo:
                // ->orWhere('economico','like',"%{$term}%")
                // ->orWhere('modelo','like',"%{$term}%");
            });
        }

        if ($request->filled('estatus')) {
            // si tienes campo estatus en Maquina
            $q->where('estatus', $request->estatus);
        }

        if ($request->filled('asignada')) {
            if ((int) $request->asignada === 1) {
                $q->whereHas('asignacionActiva');
            } else if ((int) $request->asignada === 0) {
                $q->whereDoesntHave('asignacionActiva');
            }
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $rows = $q->paginate($perPage)->withQueryString();

        return response()->json([
            'ok' => true,
            'data' => $rows->through(function ($m) {
                $asig = $m->asignacionActiva;

                return [
                    'id' => (int) $m->id,
                    'nombre' => $m->nombre ?? null,
                    'tipo'   => $m->tipo ?? null,
                    'marca'  => $m->marca ?? null,
                    'modelo' => $m->modelo ?? null,
                    'placas' => $m->placas ?? null,
                    'horometro_base'=>$m->horometro_base ?? null,
                    'estado' => $m->estado ?? null,
                    // 'economico' => $m->economico ?? null,
                    // 'estatus' => $m->estatus ?? null,

                    'asignada' => (bool) $asig,
                    'obra_activa' => $asig && $asig->obra ? [
                        'id' => (int) $asig->obra->id,
                        'nombre' => $asig->obra->nombre,
                        'clave_obra' => $asig->obra->clave_obra,
                        'estatus_nuevo' => $asig->obra->estatus_nuevo,
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

public function show(Request $request, Maquina $maquina)
{
    // Cargar asignación activa + obra
    $maquina->load([
        'asignacionActiva.obra:id,nombre,clave_obra,estatus_nuevo,ubicacion',
    ]);

    // Historial corto de asignaciones (últimas 10)
    $asignaciones = ObraMaquina::query()
        ->select([
            'id',
            'obra_id',
            'maquina_id',
            'fecha_inicio',
            'fecha_fin',
            'horometro_inicio',
            'horometro_fin',
            'estado',
        ])
        ->with(['obra:id,nombre,clave_obra,estatus_nuevo'])
        ->where('maquina_id', $maquina->id)
        ->orderByDesc('id')
        ->limit(10)
        ->get()
        ->map(function ($om) {
            return [
                'obra_maquina_id' => (int) $om->id,
                'fecha_inicio' => optional($om->fecha_inicio)->toDateString(),
                'fecha_fin' => optional($om->fecha_fin)->toDateString(),
                'estado' => $om->estado,
                'horometro_inicio' => $om->horometro_inicio,
                'horometro_fin' => $om->horometro_fin,
                'obra' => $om->obra ? [
                    'id' => (int) $om->obra->id,
                    'nombre' => $om->obra->nombre,
                    'clave_obra' => $om->obra->clave_obra,
                    'estatus_nuevo' => $om->obra->estatus_nuevo,
                ] : null,
            ];
        })
        ->values();

    $asig = $maquina->asignacionActiva;

    return response()->json([
        'ok' => true,
        'data' => [
            'maquina' => [
                'id' => (int) $maquina->id,
                'nombre' => $maquina->nombre ?? null,
                // agrega campos reales si existen:
                // 'economico' => $maquina->economico ?? null,
                // 'marca' => $maquina->marca ?? null,
                // 'modelo' => $maquina->modelo ?? null,
                // 'estatus' => $maquina->estatus ?? null,
                // 'horometro_actual' => $maquina->horometro_actual ?? null,
            ],
            'asignacion_activa' => $asig ? [
                'obra_maquina_id' => (int) $asig->id,
                'fecha_inicio' => optional($asig->fecha_inicio)->toDateString(),
                'horometro_inicio' => $asig->horometro_inicio,
                'estado' => $asig->estado,
                'obra' => $asig->obra ? [
                    'id' => (int) $asig->obra->id,
                    'nombre' => $asig->obra->nombre,
                    'clave_obra' => $asig->obra->clave_obra,
                    'estatus_nuevo' => $asig->obra->estatus_nuevo,
                    'ubicacion' => $asig->obra->ubicacion ?? null,
                ] : null,
            ] : null,
            'asignaciones_recientes' => $asignaciones,
        ],
    ]);
}
//registros
public function registros(Request $request, Maquina $maquina)
{
    // 1) Determinar la asignación activa (o la última si no hay activa)
    $asignacion = ObraMaquina::query()
        ->where('maquina_id', $maquina->id)
        ->where('estado', 'activa') // tú usas estado === 'activa'
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
        ->where('estado', 'activa')
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
