<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use Illuminate\Http\Request;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use App\Models\ObraPila;
use Illuminate\Support\Facades\DB;

class ObrasGerencialController extends Controller
{
    public function index(Request $request)
    {
        $q = Obra::query()
            ->select([
                'id',
                'cliente_id',
                'nombre',
                'clave_obra',
                'ubicacion',
                'estatus_nuevo',
                'fecha_inicio_programada',
                'fecha_inicio_real',
            ])
            ->with(['cliente:id,nombre_comercial'])
            ->orderByDesc('id');

        if ($request->filled('estatus')) {
            $q->where('estatus_nuevo', $request->estatus);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($x) use ($term) {
                $x->where('nombre', 'like', "%{$term}%")
                  ->orWhere('clave_obra', 'like', "%{$term}%")
                  ->orWhere('ubicacion', 'like', "%{$term}%");
            });
        }

     $proyectosActivosCount = DB::table('obras')
    ->whereIn('estatus_nuevo', [1,2])
    ->count();


        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $obras = $q->paginate($perPage)->withQueryString();
// $debug = [
//   'total_obras' => DB::table('obras')->count(),
//   'estatus_1'   => DB::table('obras')->where('estatus_nuevo', 1)->count(),
//   'estatus_2'   => DB::table('obras')->where('estatus_nuevo', 2)->count(),
//   'estatus_3'   => DB::table('obras')->where('estatus_nuevo', 3)->count(),
//   'in_2_3'      => DB::table('obras')->whereIn('estatus_nuevo', [2,3])->count(),
// ];
        return response()->json([
            
            'ok' => true,
            'data' => $obras->through(function ($o) {
                return [
                    'id' => (int) $o->id,
                    'cliente' => $o->cliente ? [
                        'id' => (int) $o->cliente->id,
                        'nombre' => $o->cliente->nombre_comercial,
                    ] : null,
                    'nombre' => $o->nombre,
                    'clave_obra' => $o->clave_obra,
                    'ubicacion' => $o->ubicacion,
                    'estatus_nuevo' => $o->estatus_nuevo,
                    'fecha_inicio_programada' => optional($o->fecha_inicio_programada)->toDateString(),
                    'fecha_inicio_real' => optional($o->fecha_inicio_real)->toDateString(),
                ];
            }),
            'meta' => [
                'current_page' => $obras->currentPage(),
                'last_page' => $obras->lastPage(),
                'per_page' => $obras->perPage(),
                'total' => $obras->total(),
            ],
              'counts' => [
            'proyectos_activos' => (int) $proyectosActivosCount,
        ],
        //   'debug_counts' => $debug, // 👈 temporal
        ]);
    }
    public function show(Request $request, Obra $obra)
{
    // 1) Cabecera de obra (ligera)
    $obra->load(['cliente:id,nombre_comercial']);

    // 2) KPIs / Resúmenes (ligeros)
    $empleadosCount = ObraEmpleado::query()
        ->where('obra_id', $obra->id)
        ->where('activo', 1)
        ->whereNull('fecha_baja')
        ->count();

    $maquinaActiva = ObraMaquina::query()
        ->with(['maquina:id,nombre']) // ajusta campos si necesitas
        ->where('obra_id', $obra->id)
        ->activas()
        ->latest('fecha_inicio')
        ->first();

    // 3) Empleados (preview, NO catálogo completo)
    $empleadosPreview = ObraEmpleado::query()
        ->with([
            'empleado:id_Empleado,Nombre,Apellidos,Telefono',
            'rol:id,rol_key,nombre',
        ])
        ->where('obra_id', $obra->id)
        ->where('activo', 1)
        ->whereNull('fecha_baja')
        ->orderByDesc('id')
        ->limit(10)
        ->get()
        ->map(function ($oe) {
            return [
                'obra_empleado_id' => $oe->id,
                'empleado_id'      => $oe->empleado_id,
                'rol_id'           => $oe->rol_id,
                'rol'              => $oe->rol ? [
                    'id'      => $oe->rol->id,
                    'rol_key' => $oe->rol->rol_key ?? null,
                    'nombre'  => $oe->rol->nombre ?? null,
                ] : null,
                'empleado'         => $oe->empleado ? [
                    'id_Empleado' => $oe->empleado->id_Empleado,
                    'nombre'      => trim(($oe->empleado->Nombre ?? '') . ' ' . ($oe->empleado->Apellidos ?? '')),
                    'telefono'    => $oe->empleado->Telefono ?? null, // ojo: en tu map anterior usabas telefono minúscula, aquí dejo Telefono como en select
                ] : null,
            ];
        })
        ->values();

    // 4) Pilas (si pueden ser muchas, también preview + totales)
    $pilasRaw = ObraPila::query()
        ->where('obra_id', $obra->id)
        ->orderBy('numero_pila')
        ->get();

    $pilasTotalProgramado = (int) $pilasRaw->sum('cantidad_programada');

    $pilasPreview = $pilasRaw->take(30)->map(function ($p) {
        return [
            'id' => (int) $p->id,
            'obra_id' => (int) $p->obra_id,
            'numero_pila' => $p->numero_pila ?? null,
            'tipo' => $p->tipo ?? null,
            'cantidad_programada' => $p->cantidad_programada !== null ? (int)$p->cantidad_programada : 0,
            'diametro' => $p->diametro_proyecto !== null ? (float)$p->diametro_proyecto : null,
            'profundidad' => $p->profundidad_proyecto !== null ? (float)$p->profundidad_proyecto : null,
            'ubicacion' => $p->ubicacion ?? null,
            'activo' => (bool) $p->activo,
        ];
    })->values();

    $maquinaPayload = null;
    if ($maquinaActiva) {
        $maquinaPayload = [
            'obra_maquina_id'  => $maquinaActiva->id,
            'maquina_id'       => $maquinaActiva->maquina_id,
            'fecha_inicio'     => optional($maquinaActiva->fecha_inicio)->toDateString(),
            'horometro_inicio' => $maquinaActiva->horometro_inicio,
            'estado'           => $maquinaActiva->estado,
            'maquina'          => $maquinaActiva->maquina ? [
                'id'     => $maquinaActiva->maquina->id ?? null,
                'nombre' => $maquinaActiva->maquina->nombre ?? null,
            ] : null,
        ];
    }

    return response()->json([
        'ok' => true,
        'data' => [
            'obra' => [
                'id' => (int) $obra->id,
                'cliente' => $obra->cliente ? [
                    'id' => (int) $obra->cliente->id,
                    'nombre' => $obra->cliente->nombre_comercial,
                ] : null,
                'nombre' => $obra->nombre,
                'clave_obra' => $obra->clave_obra,
                'ubicacion' => $obra->ubicacion,
                'estatus_nuevo' => $obra->estatus_nuevo,
                'fecha_inicio_programada' => optional($obra->fecha_inicio_programada)->toDateString(),
                'fecha_inicio_real' => optional($obra->fecha_inicio_real)->toDateString(),
            ],
            'kpis' => [
                'empleados_activos' => $empleadosCount,
                'pilas_total_programado' => $pilasTotalProgramado,
                'pilas_total_rows' => (int) $pilasRaw->count(),
            ],
            'maquina_activa' => $maquinaPayload,
            'empleados_preview' => $empleadosPreview,
            'pilas_preview' => $pilasPreview,
        ],
    ]);
}

}
