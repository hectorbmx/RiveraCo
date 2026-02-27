<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Services\Maquinas\MaquinaService;


class MaquinaController extends Controller
{
    //
   public function index()
{
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
        ->with(['asignacionActiva.obra:id,nombre']) // ajusta campos si obra usa "Nombre"
        ->orderBy('nombre')
        ->get();

    return view('maquinas.index', compact(
        'maquinas',
        'total',
        'porUbicacion',
        'asignadas'
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
            'seguros' => fn($q) => $q->orderByDesc('vigencia_fin'),
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
