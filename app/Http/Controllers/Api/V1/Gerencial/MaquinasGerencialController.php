<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Maquina;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use App\Models\Mantenimiento;
use App\Models\Seguro;
use Illuminate\Support\Facades\Storage;


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

        if ($request->boolean('en_uso')) {
            $q->whereHas('asignacionActiva', function ($asignacion) {
                $asignacion->where('estado', 'activa')
                    ->whereNull('fecha_fin');
            });
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
        'seguros',
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

    $seguros = $maquina->seguros()
        ->orderByDesc('vigencia_hasta')
        ->limit(10)
        ->get();

    $mantenimientos = $maquina->mantenimientos()
        ->with('mecanico:id_Empleado,Nombre,Apellidos')
        ->orderByDesc('fecha_programada')
        ->orderByDesc('id')
        ->limit(10)
        ->get();

    return response()->json([
        'ok' => true,
        'data' => [
            'maquina' => [
                'id' => (int) $maquina->id,
                'codigo' => $maquina->codigo ?? null,
                'nombre' => $maquina->nombre ?? null,
                'tipo' => $maquina->tipo ?? null,
                'marca' => $maquina->marca ?? null,
                'modelo' => $maquina->modelo ?? null,
                'numero_serie' => $maquina->numero_serie ?? null,
                'placas' => $maquina->placas ?? null,
                'color' => $maquina->color ?? null,
                'horometro_base' => $maquina->horometro_base ?? null,
                'estado' => $maquina->estado ?? null,
                'ubicacion' => $maquina->ubicacion ?? null,
                'notas' => $maquina->notas ?? null,
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
            'seguro_vigente' => $this->resolverSeguroVigente($seguros),
            'seguros_recientes' => $seguros->map(fn (Seguro $seguro) => $this->mapSeguro($seguro))->values(),
            'mantenimientos_recientes' => $mantenimientos->map(fn (Mantenimiento $mantenimiento) => $this->mapMantenimiento($mantenimiento))->values(),
            'mantenimientos_resumen' => $this->mapMantenimientosResumen($maquina),
        ],
    ]);
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

private function mapSeguro(Seguro $seguro): array
{
    return [
        'id' => (int) $seguro->id,
        'aseguradora' => $seguro->aseguradora,
        'poliza_numero' => $seguro->poliza_numero,
        'tipo_seguro' => $seguro->tipo_seguro,
        'metodo_pago' => $seguro->metodo_pago,
        'frecuencia_pago' => $seguro->frecuencia_pago,
        'costo' => $seguro->costo !== null ? (float) $seguro->costo : null,
        'moneda' => $seguro->moneda,
        'fecha_compra' => optional($seguro->fecha_compra)->format('Y-m-d'),
        'vigencia_desde' => optional($seguro->vigencia_desde)->format('Y-m-d'),
        'vigencia_hasta' => optional($seguro->vigencia_hasta)->format('Y-m-d'),
        'suma_asegurada' => $seguro->suma_asegurada !== null ? (float) $seguro->suma_asegurada : null,
        'deducible' => $seguro->deducible !== null ? (float) $seguro->deducible : null,
        'cobertura' => $seguro->cobertura,
        'estatus' => $this->estatusSeguro($seguro),
        'alerta_vencimiento_activa' => (bool) $seguro->alerta_vencimiento_activa,
        'dias_preaviso' => $seguro->dias_preaviso,
        'observaciones' => $seguro->observaciones,
        'documento_url' => $this->storageUrl($seguro->documento_path),
        'comprobante_url' => $this->storageUrl($seguro->comprobante_path),
    ];
}

private function mapMantenimiento(Mantenimiento $mantenimiento): array
{
    $mecanico = $mantenimiento->mecanico;

    return [
        'id' => (int) $mantenimiento->id,
        'tipo' => $mantenimiento->tipo,
        'categoria_mantenimiento' => $mantenimiento->categoria_mantenimiento,
        'descripcion' => $mantenimiento->descripcion,
        'km_actuales' => $mantenimiento->km_actuales,
        'km_proximo_servicio' => $mantenimiento->km_proximo_servicio,
        'horometro' => $mantenimiento->horometro,
        'fecha_programada' => optional($mantenimiento->fecha_programada)->format('Y-m-d'),
        'fecha_inicio' => optional($mantenimiento->fecha_inicio)->format('Y-m-d H:i:s'),
        'fecha_fin' => optional($mantenimiento->fecha_fin)->format('Y-m-d H:i:s'),
        'estatus' => $mantenimiento->estatus,
        'costo_total' => $mantenimiento->costo_total !== null ? (float) $mantenimiento->costo_total : null,
        'notas' => $mantenimiento->notas,
        'mecanico' => $mecanico ? [
            'id' => (int) $mecanico->id_Empleado,
            'nombre' => trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')),
        ] : null,
    ];
}

private function resolverSeguroVigente($seguros): ?array
{
    $seguros = collect($seguros);
    $hoy = now();

    $vigente = $seguros->first(function (Seguro $seguro) use ($hoy) {
        return (string) $seguro->estatus !== 'cancelada'
            && $seguro->vigencia_desde
            && $seguro->vigencia_hasta
            && $seguro->vigencia_desde->lte($hoy)
            && $seguro->vigencia_hasta->gte($hoy);
    });

    return $vigente ? $this->mapSeguro($vigente) : null;
}

private function estatusSeguro(Seguro $seguro): string
{
    if ($seguro->estatus === 'cancelada') {
        return 'cancelada';
    }

    $hoy = now();

    if ($seguro->vigencia_desde && $seguro->vigencia_desde->gt($hoy)) {
        return 'futura';
    }

    if ($seguro->vigencia_hasta && $seguro->vigencia_hasta->lt($hoy)) {
        return 'vencida';
    }

    return 'vigente';
}

private function mapMantenimientosResumen(Maquina $maquina): array
{
    $query = $maquina->mantenimientos();

    return [
        'total' => (clone $query)->count(),
        'pendiente' => (clone $query)->where('estatus', 'pendiente')->count(),
        'en_proceso' => (clone $query)->where('estatus', 'en_proceso')->count(),
        'completado' => (clone $query)->where('estatus', 'completado')->count(),
        'cancelado' => (clone $query)->where('estatus', 'cancelado')->count(),
    ];
}

private function storageUrl(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    return Storage::disk('public')->url(ltrim($path, '/'));
}
}

