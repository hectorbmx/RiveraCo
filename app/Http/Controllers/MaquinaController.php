<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\ObraMaquinaRegistro;
use App\Services\Maquinas\MaquinaService;
use App\Models\EmpresaConfig;
use App\Models\Obra;
use App\Services\Maquinas\PreventivoMaquinaService;
use App\Services\Maquinas\MaquinaHorometroService;


class MaquinaController extends Controller
{
    //
public function index(Request $request, PreventivoMaquinaService $preventivoService, MaquinaHorometroService $horometroService)
{
    $search = trim((string) $request->query('search', ''));
    $sort = (string) $request->query('sort', '');
    $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
    $sortableColumns = [
        'nombre' => 'nombre',
        'estado' => 'estado',
        'ubicacion' => 'ubicacion',
    ];
    // KPIs
    $total = Maquina::count();

    $porUbicacion = Maquina::query()
        ->selectRaw('ubicacion, COUNT(*) as total')
        ->groupBy('ubicacion')
        ->pluck('total', 'ubicacion'); // ['en_patio' => 10, ...]

    $asignadas = Maquina::query()
        ->whereHas('asignacionActiva', function ($q) {
            $q->where('estado', 'activa')->whereNull('fecha_fin');
        })
        ->count();

    // Lista
    $maquinasQuery = Maquina::query()
        ->with(['asignacionActiva.obra:id,nombre', 'seguros'])
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('tipo', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%")
                  ->orWhere('placas', 'like', "%{$search}%")
                  ->orWhere('estado', 'like', "%{$search}%")
                  ->orWhere('ubicacion', 'like', "%{$search}%")
                  ->orWhereHas('asignacionActiva.obra', function ($obraQuery) use ($search) {
                      $obraQuery->where('nombre', 'like', "%{$search}%");
                  });
            });
        });

    if (array_key_exists($sort, $sortableColumns)) {
        $maquinasQuery->orderBy($sortableColumns[$sort], $direction)
            ->orderBy('codigo')
            ->orderBy('nombre');
    } else {
        $maquinasQuery->orderBy('codigo')
            ->orderBy('nombre');
    }

    $maquinas = $maquinasQuery->get();

    $config = EmpresaConfig::first();
    $preventivos = $preventivoService->calcularParaColeccion($maquinas, $config);
    $horometrosActuales = $maquinas
        ->mapWithKeys(fn (Maquina $maquina) => [$maquina->id => $horometroService->horometroActual($maquina)])
        ->all();

    $obrasDisponibles = Obra::query()
        ->whereNotIn('estatus_nuevo', [
            Obra::ESTATUS_TERMINADA,
            Obra::ESTATUS_CANCELADA,
        ])
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'clave_obra']);

    $obrasParaAjusteHorometro = Obra::query()
        ->where('estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'clave_obra']);

    return view('maquinas.index', compact(
        'maquinas',
        'total',
        'porUbicacion',
        'asignadas',
        'preventivos',
        'horometrosActuales',
        'search',
        'sort',
        'direction',
        'obrasDisponibles',
        'obrasParaAjusteHorometro'
    ));
}
public function show(Request $request, Maquina $maquina)
{
    $tab = $request->query('tab', 'general');

    // Siempre cargamos la asignación activa + obra (para mostrar contexto)
    $maquina->load(['asignacionActiva.obra']);

    if ($tab === 'servicios') {
        $maquina->load([
            'mantenimientos' => fn($q) => $q->latest()->limit(50),
        ]);
    }

    if ($tab === 'obras') {
        $maquina->load([
            'asignaciones' => fn($q) => $q->with('obra')->orderByDesc('fecha_inicio'),
        ]);
    }

    if ($tab === 'seguros') {
        $maquina->load([
            'seguros' => fn($q) => $q->orderByDesc('vigencia_hasta'),
        ]);
    }

    if ($tab === 'kardex') {
        $maquina->load([
            'movimientos' => fn($q) => $q
                ->with(['obra:id,nombre', 'user:id,name'])
                ->orderByDesc('fecha_evento'),
        ]);
    }

    return view('maquinas.show', compact('maquina', 'tab'));
}

public function updateGeneral(Request $request, Maquina $maquina)
{
    $data = $request->validate([
        'codigo'         => ['nullable', 'string', 'max:50'],
        'nombre'         => ['required', 'string', 'max:120'],
        'tipo'           => ['nullable', 'string', 'max:120'],
        'marca'          => ['nullable', 'string', 'max:80'],
        'modelo'         => ['nullable', 'string', 'max:80'],
        'numero_serie'   => ['nullable', 'string', 'max:120'],
        'placas'         => ['nullable', 'string', 'max:30'],
        'color'          => ['nullable', 'string', 'max:50'],
        'horometro_base' => ['nullable', 'numeric', 'min:0'],
        'notas'          => ['nullable', 'string', 'max:2000'],
    ]);

    $maquina->update($data);

    return redirect()
        ->route('maquinas.show', ['maquina' => $maquina->id, 'tab' => 'general'])
        ->with('success', 'Datos generales de la máquina actualizados.');
}
public function cambiarEstado(Request $request, Maquina $maquina, MaquinaService $svc)
{
    \Log::info('MAQUINA cambiarEstado HIT', [
  'maquina_id' => $maquina->id,
  'estado_actual' => $maquina->estado,
  'payload' => $request->all(),
]);
    $data = $request->validate([
        'estado' => ['required', 'string'],
        'motivo' => ['nullable', 'string', 'max:190'],
        'notas'  => ['nullable', 'string', 'max:2000'],
    ]);

    $asig = $maquina->asignacionActiva;
    $obraId = $asig?->obra_id;
    $obraMaquinaId = $asig?->id;

    try {
        $svc->cambiarEstado(
            maquina: $maquina,
            nuevoEstado: $data['estado'],
            obraId: $obraId,
            obraMaquinaId: $obraMaquinaId,
            motivo: $data['motivo'] ?? null,
            notas: $data['notas'] ?? null
        );

        return back()->with('success', 'Estado actualizado correctamente.');
    } catch (\Throwable $e) {
        // return back()->withErrors(['general' => $e->getMessage()]);
        
    }
}
public function toggleServicio(Request $request, Maquina $maquina, MaquinaService $svc)
{
    // Solo operativa <-> fuera_servicio
    if (!in_array($maquina->estado, ['operativa', 'fuera_servicio'], true)) {
        return back()->withErrors(['general' => 'Este estado no se puede cambiar desde aquí.']);
    }

    $data = $request->validate([
        'motivo' => ['nullable', 'string', 'max:190'],
        'notas'  => ['nullable', 'string', 'max:2000'],
    ]);

    $nuevo = $maquina->estado === 'operativa' ? 'fuera_servicio' : 'operativa';

    // Contexto para el log (si está asignada a obra)
    $asig = $maquina->asignacionActiva; // relación existente
    $obraId = $asig?->obra_id;
    $obraMaquinaId = $asig?->id;

    try {
        $svc->cambiarEstado(
            maquina: $maquina,
            nuevoEstado: $nuevo,
            obraId: $obraId,
            obraMaquinaId: $obraMaquinaId,
            motivo: $data['motivo'] ?? null,
            notas: $data['notas'] ?? null
        );

        return back()->with('success', 'Estado actualizado correctamente.');
    } catch (\Throwable $e) {
        return back()->withErrors(['general' => $e->getMessage()]);
    }
}
public function guardarHoras(Request $request, Maquina $maquina, MaquinaHorometroService $horometroService)
{
    abort_unless($request->user()?->can('maquinas.horas.create.access'), 403);

    $data = $request->validate([
        'horometro_fin' => ['required', 'numeric', 'min:0'],
        'inicio'        => ['nullable', 'date'],
        'fin'           => ['nullable', 'date', 'after_or_equal:inicio'],
        'obra_id'       => ['nullable', 'exists:obras,id'],
        'notas'         => ['nullable', 'string', 'max:500'],
    ]);

    $asignacion = $maquina->asignacionActiva()->with('obra')->first();

    try {
        if ($asignacion) {
            $horometroService->crearRegistro($asignacion, $data, $request->user(), 'web_maquinas');

            return back()->with('success', 'Horas registradas correctamente.');
        }

        $horometroService->crearAjuste($maquina, [
            'horometro_nuevo' => $data['horometro_fin'],
            'obra_id' => $data['obra_id'] ?? null,
            'tipo' => 'ajuste',
            'notas' => $data['notas'] ?? null,
        ], $request->user(), 'web_maquinas');
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['horometro_fin' => $e->getMessage()])
            ->withInput();
    }

    return back()->with('success', 'Horometro ajustado correctamente.');
}

public function asignarObra(Request $request, Maquina $maquina, MaquinaService $maquinaService)
{
    $data = $request->validate([
        'obra_id'          => ['required', 'exists:obras,id'],
        'fecha_inicio'     => ['required', 'date'],
        'horometro_inicio' => ['required', 'numeric', 'min:0'],
        'notas'            => ['nullable', 'string', 'max:1000'],
    ]);

    $obra = Obra::findOrFail($data['obra_id']);

    try {
        $maquinaService->asignarAObra($maquina, $obra, $data);

        return back()->with('success', "Máquina asignada correctamente a la obra '{$obra->nombre}'.");
    } catch (\Throwable $e) {
        return back()->withErrors(['general' => $e->getMessage()]);
    }
}

//para el desglose de pilas despues de seleccionar maquinas ** revisar codex
public function pilasActivasObra(Request $request, Obra $obra)
{
    abort_unless(
        $request->user()?->can('maquinas.asignar_obra.access'),
        403
    );

    $pilas = $obra->pilas()
        ->where('activo', true)
        ->withSum(
            'detallesComision as cantidad_ejecutada',
            'cantidad'
        )
        ->orderBy('numero_pila')
        ->get()
        ->map(function ($pila) {
            $programadas = (float) ($pila->cantidad_programada ?? 0);
            $ejecutadas  = (float) ($pila->cantidad_ejecutada ?? 0);
            $faltantes   = max($programadas - $ejecutadas, 0);

            return [
                'id' => $pila->id,
                'numero_pila' => $pila->numero_pila,
                'tipo' => $pila->tipo ?: '—',
                'cantidad_programada' => $programadas,
                'cantidad_ejecutada' => $ejecutadas,
                'cantidad_faltante' => $faltantes,

                'diametro_proyecto' => is_null($pila->diametro_proyecto)
                    ? null
                    : (float) $pila->diametro_proyecto,

                'profundidad_proyecto' => is_null($pila->profundidad_proyecto)
                    ? null
                    : (float) $pila->profundidad_proyecto,

                'ubicacion' => $pila->ubicacion ?: '—',
            ];
        })
        ->values();

    return response()->json([
        'obra' => [
            'id' => $obra->id,
            'clave' => $obra->clave_obra,
            'nombre' => $obra->nombre,
        ],
        'pilas' => $pilas,
        'resumen' => [
            'tipos_activos' => $pilas->count(),
            'programadas' => $pilas->sum('cantidad_programada'),
            'ejecutadas' => $pilas->sum('cantidad_ejecutada'),
            'faltantes' => $pilas->sum('cantidad_faltante'),
        ],
    ]);
}
}


