<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\SatCfdi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ObraReposicionGasto;
use App\Models\ObraReposicionGastoDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CuentaBancoEmpresa;
use App\Models\MetodoPagoEmpresa;


class ObraReposicionGastoController extends Controller
{

 
    public function buscarCfdis(Request $request, Obra $obra)
    {
        $request->validate([
            'rfc_emisor' => 'nullable|string|max:20',
            'fecha'      => 'nullable|date',
            'monto'      => 'nullable|numeric|min:0',
            'uuid4'      => 'nullable|string|max:4',
        ]);

        $rfcEmpresa = 'RCO820921T66';

        $cfdis = SatCfdi::query()
            ->where('rfc_emisor', '!=', $rfcEmpresa)
            ->where('rfc_receptor', $rfcEmpresa)

            ->when($request->rfc_emisor, function ($query, $rfcEmisor) {
                $query->where('rfc_emisor', 'like', '%' . trim($rfcEmisor) . '%');
            })

            ->when($request->fecha, function ($query, $fecha) {
                $query->whereDate('fecha_emision', $fecha);
            })

            ->when($request->monto, function ($query, $monto) {
                $query->where('total', (float) $monto);
            })

            ->when($request->uuid4, function ($query, $uuid4) {
                $query->where('uuid', 'like', '%' . trim($uuid4));
            })

            ->latest('fecha_emision')
            ->limit(20)
            ->get([
                'id',
                'uuid',
                'fecha_emision',
                'rfc_emisor',
                'emisor_nombre',
                'rfc_receptor',
                'receptor_nombre',
                'subtotal',
                'total',
                'moneda',
                'metodo_pago',
                'forma_pago',
            ]);

        return response()->json([
            'ok' => true,
            'data' => $cfdis->map(function ($cfdi) {
                return [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                    'uuid_corto' => $cfdi->uuid ? substr($cfdi->uuid, -4) : null,
                    'fecha' => optional($cfdi->fecha_emision)->format('Y-m-d'),
                    'fecha_formateada' => optional($cfdi->fecha_emision)->format('d/m/Y'),
                    'rfc_emisor' => $cfdi->rfc_emisor,
                    'emisor_nombre' => $cfdi->emisor_nombre,
                    'rfc_receptor' => $cfdi->rfc_receptor,
                    'receptor_nombre' => $cfdi->receptor_nombre,
                    'subtotal' => (float) $cfdi->subtotal,
                    'total' => (float) $cfdi->total,
                    'moneda' => $cfdi->moneda ?? 'MXN',
                    'metodo_pago' => $cfdi->metodo_pago,
                    'forma_pago' => $cfdi->forma_pago,
                ];
            }),
        ]);
    }

    public function store(Request $request, Obra $obra)
{
   $request->merge([
        'conceptos' => json_decode($request->conceptos ?? '[]', true) ?? []
    ]);
    $request->validate([
        'tipo_reposicion' => 'required|in:caja_chica,viaticos,gastos_varios',
        'partida_id' => 'required',
        'semana' => 'required',
        'conceptos' => 'required|array|min:1',
        'conceptos.*.tipo' => 'required|string',
        'conceptos.*.monto' => 'required|numeric|min:0.01',
    ]);

    DB::beginTransaction();

    try {

        $reposicion = ObraReposicionGasto::create([

            'obra_id' => $obra->id,
            'tipo_reposicion' => $request->tipo_reposicion,
            'partida_id' => $request->partida_id,
            'semana' => $request->semana,
            'estatus' => 'solicitado',
            'observaciones' => $request->observaciones,
            'solicitado_por' => auth()->id(),
            'solicitado_at' => now(),
            'total' => collect($request->conceptos)->sum('monto'),
        ]);

        foreach ($request->conceptos as $concepto) {

            ObraReposicionGastoDetalle::create([

                'obra_reposicion_gasto_id' => $reposicion->id,
                'tipo' => $concepto['tipo'] ?? null,
                'descripcion' => $concepto['descripcion'] ?? null,
                'proveedor' => $concepto['proveedor'] ?? null,
                'rfc' => $concepto['rfc'] ?? null,
                'uuid' => $concepto['uuid'] ?? null,
                'fecha' => $concepto['fecha'] ?? null,
                'monto' => $concepto['monto'] ?? 0,
                'sat_cfdi_id' => $concepto['sat_cfdi_id'] ?? null,
                'partida_id' => $concepto['partida_id'] ?? $request->partida_id,

            ]);
        }

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Reposición registrada correctamente.');
    } catch (\Throwable $e) {

        DB::rollBack();
        report($e);
        return redirect()
            ->back()
            ->with('error', 'Error al guardar la reposición.');

    }
}

public function show(Obra $obra, ObraReposicionGasto $reposicion)
{
    abort_if($reposicion->obra_id !== $obra->id, 404);

    $reposicion->load([
        'partida',
        'detalles.cfdi',
        'solicitadoPor',
        'revisadoPor',
        'aprobadoPor',
        'pagadoPor',
    ]);
    $cuentasBanco = CuentaBancoEmpresa::where('activa', true)
    ->orderByDesc('principal')
    ->orderBy('banco')
    ->orderBy('nombre')
    ->get();

    $metodosPago = MetodoPagoEmpresa::where('activo', true)
        ->orderBy('nombre')
        ->get();

    return view('obras.reposicion-gastos.show', [
        'obra' => $obra,
        'reposicion' => $reposicion,
        'cuentasBanco' => $cuentasBanco,
        'metodosPago' => $metodosPago
    ]);
}

public function pdf(Obra $obra, ObraReposicionGasto $reposicion)
{
    abort_if($reposicion->obra_id !== $obra->id, 404);

    $reposicion->load([
        'partida',
        'detalles.partida',
        'detalles.cfdi',
        'solicitadoPor',
        'revisadoPor',
        'aprobadoPor',
        'pagadoPor',
    ]);

    $pdf = Pdf::loadView('obras.reposicion-gastos.pdf', [
        'obra' => $obra,
        'reposicion' => $reposicion,
    ])->setPaper('letter', 'portrait');

    return $pdf->stream('reposicion-gastos-REP-' . str_pad($reposicion->id, 5, '0', STR_PAD_LEFT) . '.pdf');
}
public function programar(Request $request, Obra $obra, ObraReposicionGasto $reposicion)
{
    abort_if($reposicion->obra_id !== $obra->id, 404);

    abort_unless(
        auth()->user()->can('reposicion_gastos.programar.access'),
        403
    );

    if ($reposicion->estatus !== 'solicitado') {
        return back()->with('error', 'Esta reposición no está pendiente de programación.');
    }

    $request->validate([
        'fecha_programada_pago' => 'required|date',
        'comentarios_revision' => 'nullable|string|max:3000',
    ]);

    $reposicion->update([
        'estatus' => 'programado_area',
        'revisado_por' => auth()->id(),
        'revisado_at' => now(),
        'fecha_programada_pago' => $request->fecha_programada_pago,
        'comentarios_revision' => $request->comentarios_revision,
    ]);

    return back()->with('success', 'Reposición programada correctamente.');
}
public function aprovisionar(Request $request, Obra $obra, ObraReposicionGasto $reposicion)
{
    abort_if($reposicion->obra_id !== $obra->id, 404);

    abort_unless(
        auth()->user()->can('reposicion_gastos.aprovisionar.access'),
        403
    );

    if ($reposicion->estatus !== 'programado_area') {
        return back()->with('error', 'Esta reposición no está lista para aprovisionamiento.');
    }

    $data = $request->validate([
        'cuenta_banco_empresa_id' => ['required', 'exists:cuentas_banco_empresa,id'],
        'metodo_pago_empresa_id' => ['required', 'exists:metodos_pago_empresa,id'],
        'fecha_salida_programada' => ['required', 'date'],
        'comentarios_aprovisionamiento' => ['nullable', 'string', 'max:2000'],
    ]);

    DB::transaction(function () use ($reposicion, $data) {
        $reposicion->update([
            'cuenta_banco_empresa_id' => $data['cuenta_banco_empresa_id'],
            'metodo_pago_empresa_id' => $data['metodo_pago_empresa_id'],
            'fecha_salida_programada' => $data['fecha_salida_programada'],
            'comentarios_aprovisionamiento' => $data['comentarios_aprovisionamiento'] ?? null,

            'aprovisionado_por' => auth()->id(),
            'aprovisionado_at' => now(),

            'estatus' => 'pendiente_autorizacion',
        ]);
    });

    return redirect()
        ->route('obras.reposicion-gastos.show', [$obra, $reposicion])
        ->with('success', 'Reposición aprovisionada correctamente. Queda pendiente de autorización.');
}
public function autorizar(Request $request, Obra $obra, ObraReposicionGasto $reposicion)
{
    abort_if($reposicion->obra_id !== $obra->id, 404);

    abort_unless(
        auth()->user()->can('reposicion_gastos.autorizar.access'),
        403
    );

    if ($reposicion->estatus !== 'pendiente_autorizacion') {
        return back()->with('error', 'Esta reposición no está pendiente de autorización.');
    }

    $data = $request->validate([
        'comentarios_autorizacion' => ['nullable', 'string', 'max:2000'],
    ]);

    $reposicion->update([
        'estatus' => 'autorizado',

        'aprobado_por' => auth()->id(),
        'aprobado_at' => now(),

        'comentarios_autorizacion' => $data['comentarios_autorizacion'] ?? null,
    ]);

    return back()->with('success', 'Reposición autorizada correctamente.');
}
public function rechazar(Request $request, Obra $obra, ObraReposicionGasto $reposicion)
{
    abort_if($reposicion->obra_id !== $obra->id, 404);

    abort_unless(
        auth()->user()->can('reposicion_gastos.autorizar.access'),
        403
    );

    if ($reposicion->estatus !== 'pendiente_autorizacion') {
        return back()->with('error', 'Esta reposición no está pendiente de autorización.');
    }

    $data = $request->validate([
        'comentarios_autorizacion' => ['required', 'string', 'max:2000'],
    ]);

    $reposicion->update([
        'estatus' => 'rechazado',

        'aprobado_por' => auth()->id(),
        'aprobado_at' => now(),

        'comentarios_autorizacion' => $data['comentarios_autorizacion'],
    ]);

    return back()->with('success', 'Reposición rechazada correctamente.');
}
}