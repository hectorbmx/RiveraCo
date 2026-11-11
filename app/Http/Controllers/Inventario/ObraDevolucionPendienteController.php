<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Services\Obras\ObraDevolucionPendienteConsultaService;
use App\Services\Obras\ObraDevolucionRecepcionService;
use App\Models\Almacen;
use App\Models\ObraDevolucion;
use Illuminate\Http\Request;

class ObraDevolucionPendienteController extends Controller
{
    public function index(Request $request, ObraDevolucionPendienteConsultaService $consulta)
    {
        abort_unless($request->user()?->can('obra.devoluciones.view.access') && $request->user()?->can('obra.devoluciones.receive.access'), 403);
        $datos = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $solicitudes = $consulta->consultarPreparadas($datos['q'] ?? null);
        return view('inventario.devoluciones-obra.index', [
            'solicitudes' => $solicitudes, 'busqueda' => $datos['q'] ?? '',
        ]);
    }

    public function create(Request $request, ObraDevolucion $devolucion, ObraDevolucionPendienteConsultaService $consulta)
    {
        abort_unless($request->user()?->can('obra.devoluciones.view.access') && $request->user()?->can('obra.devoluciones.receive.access'), 403);
        $solicitud = $consulta->consultar(null, $devolucion->id)->firstOrFail();
        return view('inventario.devoluciones-obra.recibir', [
            'solicitud' => $solicitud,
            'almacenes' => Almacen::where('activo', true)->orderBy('nombre')->get(),
            'operacionUuid' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }

    public function store(Request $request, ObraDevolucion $devolucion, ObraDevolucionRecepcionService $servicio)
    {
        abort_unless($request->user()?->can('obra.devoluciones.view.access') && $request->user()?->can('obra.devoluciones.receive.access'), 403);
        $datos = $request->all();
        $datos['partidas'] = collect($datos['partidas'] ?? [])->filter(fn ($p) => is_array($p) && isset($p['cantidad']) && trim((string) $p['cantidad']) !== '')->values()->all();
        $recepcion = $servicio->confirmar($devolucion, $request->user(), $datos);
        return redirect()->route('inventario.documentos.index', ['tipo' => 'devolucion', 'obra_material_id' => $devolucion->obra_id])->with('status', 'Recepción física '.$recepcion->folio.' registrada; pendiente de aplicación a inventario y costos.');
    }

    public function aplicar(Request $request, \App\Models\ObraDevolucionRecepcion $recepcion, \App\Services\Obras\ObraDevolucionAplicacionService $servicio)
    {
        abort_unless($request->user()?->can('obra.devoluciones.view.access') && $request->user()?->can('obra.devoluciones.receive.access'), 403);
        $resultado = $servicio->aplicar($recepcion, $request->user());
        $mensaje = $resultado->estado === 'aplicada'
            ? 'Recepción '.$resultado->folio.' aplicada a inventario y al costo de materiales de la obra.'
            : 'Partidas aptas de '.$resultado->folio.' aplicadas; las demás permanecen pendientes de aclaración o evaluación.';
        return redirect()->route('inventario.documentos.index', ['tipo' => 'devolucion'])->with('status', $mensaje);
    }

    public function registrarEvaluacion(Request $request, \App\Models\ObraDevolucionRecepcion $recepcion, \App\Services\Obras\ObraDevolucionEvaluacionPendienteService $servicio)
    {
        abort_unless($request->user()?->can('obra.devoluciones.view.access') && $request->user()?->can('obra.devoluciones.receive.access'), 403);
        $cantidad = $servicio->registrar($recepcion, $request->user());
        return redirect()->route('inventario.documentos.index', ['tipo' => 'devolucion'])->with('status', $cantidad ? 'Herramientas registradas pendientes de evaluación; no están disponibles en inventario.' : 'Las herramientas ya estaban registradas pendientes de evaluación.');
    }
}
