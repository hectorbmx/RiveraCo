<?php

namespace App\Http\Controllers;

use App\Models\ObraReposicionGasto;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Obra;


class CajaChicaController extends Controller
{
    public function index(Request $request)
{
    $fechaInicio = $request->filled('fecha_inicio')
        ? Carbon::parse($request->fecha_inicio)->startOfDay()
        : now()->startOfWeek();

    $fechaFin = $request->filled('fecha_fin')
        ? Carbon::parse($request->fecha_fin)->endOfDay()
        : now()->endOfWeek();

    $reposiciones = ObraReposicionGasto::query()
        ->with([
            'obra',
            'partida',
            'solicitadoPor',
            'revisadoPor',
            'aprovisionadoPor',
            'aprobadoPor',
            'pagadoPor',
            'cuentaBancoEmpresa',
            'metodoPagoEmpresa',
        ])
        ->withCount('detalles')
        ->whereBetween('solicitado_at', [$fechaInicio, $fechaFin])
        ->when($request->filled('obra_id'), function ($query) use ($request) {
            $query->where('obra_id', $request->obra_id);
        })
        ->when($request->filled('tipo_reposicion'), function ($query) use ($request) {
            $query->where('tipo_reposicion', $request->tipo_reposicion);
        })
        ->latest('id')
        ->paginate(20)
        ->withQueryString();

    $obras = Obra::orderBy('nombre')->get();

    $semanaAnteriorInicio = $fechaInicio->copy()->subWeek()->format('Y-m-d');
    $semanaAnteriorFin = $fechaFin->copy()->subWeek()->format('Y-m-d');

    $semanaSiguienteInicio = $fechaInicio->copy()->addWeek()->format('Y-m-d');
    $semanaSiguienteFin = $fechaFin->copy()->addWeek()->format('Y-m-d');

    return view('cajas-chicas.index', compact(
        'reposiciones',
        'obras',
        'fechaInicio',
        'fechaFin',
        'semanaAnteriorInicio',
        'semanaAnteriorFin',
        'semanaSiguienteInicio',
        'semanaSiguienteFin'
    ));
}
}