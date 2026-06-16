<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraSolicitudGasto;
use App\Models\ObraSolicitudGastoDetalle;
use App\Models\ObraPlaneacionGasto;
use App\Models\ObraPlaneacionSemanal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ObraSolicitudGastoController extends Controller
{
    /**
     * Mostrar formulario para crear una nueva solicitud
     * Se basa en la planeación de una semana específica.
     */
    public function create(Request $request, Obra $obra)
    {
        $semana = $request->query('semana');
        
        // Obtenemos los IDs de presupuestos vinculados a la obra
        $presupuestoIds = $obra->presupuestos_vinculados()->pluck('presupuestos.id');

        // Obtenemos los conceptos planeados para esa semana que tengan monto > 0
        $conceptosPlaneados = ObraPlaneacionSemanal::query()
            ->whereHas('gastoBase', function($q) use ($obra, $presupuestoIds) {
                $q->where(function($sub) use ($obra, $presupuestoIds) {
                    $sub->where('obra_id', $obra->id)
                        ->orWhereIn('presupuesto_id', $presupuestoIds);
                });
            })
            ->where('numero_semana', $semana)
            ->where('monto_programado', '>', 0)
            ->with('gastoBase')
            ->get();

        return view('obras.solicitudes.create', compact('obra', 'semana', 'conceptosPlaneados'));
    }

    /**
     * Guardar la solicitud
     */
    public function store(Request $request, Obra $obra)
    {
        $request->validate([
            'semana' => 'required|integer',
            'conceptos' => 'required|array',
            'conceptos.*.monto' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $solicitud = ObraSolicitudGasto::create([
                'obra_id' => $obra->id,
                'semana' => $request->semana,
                'estatus' => 'solicitado',
                'solicitado_por' => Auth::id(),
                'solicitado_at' => now(),
                'observaciones' => $request->observaciones,
                'total' => 0, // Se calculará abajo
            ]);

            $total = 0;
            foreach ($request->conceptos as $planeacion_gasto_id => $data) {
                $monto = (float) $data['monto'];
                if ($monto > 0) {
                    ObraSolicitudGastoDetalle::create([
                        'obra_solicitud_gasto_id' => $solicitud->id,
                        'planeacion_gasto_id' => $planeacion_gasto_id,
                        'monto_solicitado' => $monto,
                    ]);
                    $total += $monto;
                }
            }

            $solicitud->update(['total' => $total]);

            DB::commit();

            return redirect()->route('obras.edit', ['obra' => $obra->id, 'tab' => 'solicitudes-gastos'])
                ->with('success', 'Solicitud de gasto enviada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar los detalles de una solicitud
     */
    public function show(Obra $obra, $solicitud_id)
    {
        $solicitud = ObraSolicitudGasto::with([
                'detalles.planeacionGasto',
                'solicitadoPor',
                'autorizadoPor',
                'pagadoPor'
            ])
            ->where('obra_id', $obra->id)
            ->findOrFail($solicitud_id);

        return view('obras.solicitudes.show', compact('obra', 'solicitud'));
    }

    /**
     * Autorizar solicitud
     */
    public function autorizar(Obra $obra, $solicitud_id)
    {
        $solicitud = ObraSolicitudGasto::where('obra_id', $obra->id)->findOrFail($solicitud_id);

        if ($solicitud->estatus !== 'solicitado') {
            return back()->with('error', 'La solicitud ya no se encuentra en estatus solicitado.');
        }

        $solicitud->update([
            'estatus' => 'autorizado',
            'autorizado_por' => Auth::id(),
            'autorizado_at' => now(),
        ]);

        return back()->with('success', 'Solicitud autorizada correctamente.');
    }

    /**
     * Rechazar solicitud
     */
    public function rechazar(Obra $obra, $solicitud_id)
    {
        $solicitud = ObraSolicitudGasto::where('obra_id', $obra->id)->findOrFail($solicitud_id);

        if ($solicitud->estatus !== 'solicitado') {
            return back()->with('error', 'La solicitud ya no se encuentra en estatus solicitado.');
        }

        $solicitud->update([
            'estatus' => 'rechazado',
        ]);

        return back()->with('success', 'Solicitud rechazada correctamente.');
    }
}
