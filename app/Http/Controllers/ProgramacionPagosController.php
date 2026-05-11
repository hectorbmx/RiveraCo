<?php

namespace App\Http\Controllers;

use App\Models\SatCfdi;
use Illuminate\Http\Request;
use App\Models\SatCfdiProgramacion;
use Illuminate\Support\Facades\DB;

class ProgramacionPagosController extends Controller
{
    public function index(Request $request)
    {
        $rfcEmpresa = 'RCO820921T66';
        $fechaInicio = $request->fecha_inicio
            ? \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay()
            : now()->startOfWeek()->startOfDay();

        $fechaFin = $request->fecha_fin
            ? \Carbon\Carbon::parse($request->fecha_fin)->endOfDay()
            : now()->endOfWeek()->endOfDay();

        $semanaAnteriorInicio = $fechaInicio->copy()->subWeek()->format('Y-m-d');
        $semanaAnteriorFin = $fechaFin->copy()->subWeek()->format('Y-m-d');
        $semanaSiguienteInicio = $fechaInicio->copy()->addWeek()->format('Y-m-d');
        $semanaSiguienteFin = $fechaFin->copy()->addWeek()->format('Y-m-d');      
        
        $cfdis = SatCfdi::query()

            ->with([
                'obra',
                'conceptos',
                'programaciones' => function ($query) {
                    $query->latest();
                }
            ])

            ->where('rfc_emisor', '!=', $rfcEmpresa)
            ->where('rfc_receptor', $rfcEmpresa)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->when($request->sat_empresa_id, function ($query, $satEmpresaId) {
                $query->where('sat_empresa_id', $satEmpresaId);
            })
            ->when($request->rfc_emisor, function ($query, $rfcEmisor) {
                $query->where('rfc_emisor', 'like', "%{$rfcEmisor}%");
            })
            ->when($request->q, function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('emisor_nombre', 'like', "%{$q}%")
                        ->orWhere('rfc_emisor', 'like', "%{$q}%")
                        ->orWhere('uuid', 'like', "%{$q}%");
                });
            })
            ->when($request->metodo_pago, function ($query, $metodoPago) {
                $query->where('metodo_pago', $metodoPago);
            })
            ->latest('fecha_emision')
            ->paginate(20)
            ->withQueryString();
       return view('programacion_pagos.index', compact(
            'cfdis',
            'rfcEmpresa',
            'fechaInicio',
            'fechaFin',
            'semanaAnteriorInicio',
            'semanaAnteriorFin',
            'semanaSiguienteInicio',
            'semanaSiguienteFin'
        ));
    }
    public function store(Request $request)
            {
                $request->validate([
                    'sat_cfdi_id'       => 'required|exists:sat_cfdis,id',
                    'fecha_programada' => 'required|date',
                    'observaciones'    => 'nullable|string',
                ]);

                DB::beginTransaction();

                try {

                    $cfdi = SatCfdi::findOrFail($request->sat_cfdi_id);

                    /*
                    |--------------------------------------------------------------------------
                    | EVITAR DUPLICADOS ACTIVOS
                    |--------------------------------------------------------------------------
                    */

                    $existe = SatCfdiProgramacion::query()
                        ->where('sat_cfdi_id', $cfdi->id)
                        ->whereNotIn('estatus', [
                            'cancelada',
                            'pagada',
                        ])
                        ->exists();

                    if ($existe) {

                        return back()->with('error', 'El CFDI ya tiene una programación activa.');

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CREAR PROGRAMACION
                    |--------------------------------------------------------------------------
                    */

                    SatCfdiProgramacion::create([

                        'sat_cfdi_id' => $cfdi->id,

                        'cfdi_uuid' => $cfdi->uuid,

                        'origen' => 'cfdi',

                        'area' => auth()->user()->area ?? null,

                        'proveedor_nombre' => $cfdi->emisor_nombre,

                        'proveedor_rfc' => $cfdi->rfc_emisor,

                        'concepto' => $cfdi->concepto ?? $cfdi->emisor_nombre,

                        'fecha_gasto' => $cfdi->fecha_emision,

                        'fecha_programada' => $request->fecha_programada,

                        'monto_programado' => $cfdi->total,

                        'moneda' => $cfdi->moneda ?? 'MXN',

                        'tipo_cambio' => $cfdi->tipo_cambio,

                        'requiere_factura' => true,

                        'estatus_factura' => 'recibida',

                        'estatus' => 'pendiente_revision_admin',

                        'tipo_pago' => $cfdi->metodo_pago,

                        'solicitado_by' => auth()->id(),

                        'solicitado_at' => now(),

                        'observaciones' => $request->observaciones,

                        'created_by' => auth()->id(),

                        'updated_by' => auth()->id(),

                    ]);

                    DB::commit();

                    return back()->with('success', 'Programación registrada correctamente.');

                } catch (\Throwable $th) {

                    DB::rollBack();

                    return back()->with('error', $th->getMessage());
                }
        }

        public function revisar(Request $request, SatCfdiProgramacion $programacion)
{
    if (! auth()->user()->hasAnyRole([ 'admin-rivera', 'super-admin'])) {
        abort(403, 'No tienes permiso para revisar esta programación.');
    }

    if ($programacion->estatus !== 'pendiente_revision_admin') {
        return back()->with('error', 'Esta programación no está pendiente de revisión administrativa.');
    }

    $request->validate([
        'comentario_revision' => 'nullable|string|max:2000',
    ]);

    $programacion->update([
        'estatus' => 'pendiente_aprobacion_ceo',
        'revisado_by' => auth()->id(),
        'revisado_at' => now(),
        'comentario_revision' => $request->comentario_revision,
        'updated_by' => auth()->id(),
    ]);

    return back()->with('success', 'Programación revisada correctamente. Ahora queda pendiente de autorización CEO.');
}

public function autorizar(Request $request, SatCfdiProgramacion $programacion)
{
    if (! auth()->user()->hasAnyRole(['admin-rivera', 'super-admin'])) {
        abort(403, 'No tienes permiso para autorizar esta programación.');
    }

    if ($programacion->estatus !== 'pendiente_aprobacion_ceo') {
        return back()->with('error', 'Esta programación no está pendiente de autorización CEO.');
    }

    $request->validate([
        'comentario_aprobacion' => 'nullable|string|max:2000',
    ]);

    $programacion->update([
        'estatus' => 'aprobada',
        'aprobado_by' => auth()->id(),
        'aprobado_at' => now(),
        'comentario_aprobacion' => $request->comentario_aprobacion,
        'updated_by' => auth()->id(),
    ]);

    return back()->with('success', 'Programación autorizada correctamente.');
}
}