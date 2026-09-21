<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Services\Maquinas\MaquinaService;
use App\Models\EmpresaConfig;
use App\Services\Maquinas\PreventivoMaquinaService;


class MaquinaController extends Controller
{
    //
public function index(Request $request, PreventivoMaquinaService $preventivoService)
{
    $search = trim((string) $request->query('search', ''));

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
    $maquinas = Maquina::query()
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
        })
        ->orderBy('codigo')
        ->orderBy('nombre')
        ->get();

    $config = EmpresaConfig::first();
    $preventivos = $preventivoService->calcularParaColeccion($maquinas, $config);

    return view('maquinas.index', compact(
        'maquinas',
        'total',
        'porUbicacion',
        'asignadas',
        'preventivos',
        'search'
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
}

