<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\SatCfdi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'conceptos' => json_decode($request->conceptos ?? '[]', true) ?? [],
        ]);

        $tipoReposicion = $request->input('tipo_reposicion');

        $rules = [
            'tipo_reposicion' => 'required|in:caja_chica,viaticos,gastos_varios',
            'partida_id' => 'required',
            'semana' => 'required',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.tipo' => 'required|string',
            'conceptos.*.descripcion' => 'nullable|string|max:1000',
            'conceptos.*.proveedor' => 'nullable|string|max:255',
            'conceptos.*.rfc' => 'nullable|string|max:20',
            'conceptos.*.uuid' => 'nullable|string|max:80',
            'conceptos.*.fecha' => 'nullable|date',
            'conceptos.*.fecha_inicio' => 'nullable|date',
            'conceptos.*.fecha_fin' => 'nullable|date',
            'conceptos.*.monto' => 'required|numeric|min:0.01',
            'conceptos.*.comprobante_tipo' => 'nullable|in:cfdi,nota,viatico',
            'conceptos.*.numero_nota' => 'nullable|string|max:80',
            'conceptos.*.dias' => 'nullable|integer|min:1|max:365',
            'conceptos.*.importe_unitario' => 'nullable|numeric|min:0.01',
            'conceptos.*.sat_cfdi_id' => 'nullable|exists:sat_cfdis,id',
            'conceptos.*.empresa_viatico_tarifa_id' => 'nullable|exists:empresa_viatico_tarifas,id',
            'conceptos.*.obra_empleado_id' => 'nullable|exists:obra_empleado,id',
            'conceptos.*.partida_id' => 'nullable|integer',
        ];

        if ($tipoReposicion === 'caja_chica') {
            $rules['conceptos.*.sat_cfdi_id'] = 'required|exists:sat_cfdis,id';
            $rules['conceptos.*.uuid'] = 'required|string';
        }

        if ($tipoReposicion === 'viaticos') {
            $rules['conceptos.*.fecha_inicio'] = 'required|date';
            $rules['conceptos.*.fecha_fin'] = 'required|date';
            $rules['conceptos.*.descripcion'] = 'required|string|max:1000';
            $rules['conceptos.*.importe_unitario'] = 'required|numeric|min:0.01';
            $rules['conceptos.*.empresa_viatico_tarifa_id'] = 'required|exists:empresa_viatico_tarifas,id';
            $rules['conceptos.*.obra_empleado_id'] = 'required|exists:obra_empleado,id';
        }

        $validated = $request->validate($rules);

        if ($tipoReposicion === 'viaticos') {
            $empleadoIds = collect($validated['conceptos'])
                ->pluck('obra_empleado_id')
                ->filter()
                ->unique()
                ->values();

            $empleadosValidos = ObraEmpleado::query()
                ->where('obra_id', $obra->id)
                ->where('activo', true)
                ->whereIn('id', $empleadoIds)
                ->count();

            if ($empleadosValidos !== $empleadoIds->count()) {
                throw ValidationException::withMessages([
                    'conceptos' => 'Selecciona empleados activos asignados a esta obra para registrar viaticos.',
                ]);
            }
        }

        $conceptos = collect($validated['conceptos'])->map(function (array $concepto) use ($tipoReposicion) {
            if ($tipoReposicion === 'viaticos') {
                $inicio = Carbon::parse($concepto['fecha_inicio'])->startOfDay();
                $fin = Carbon::parse($concepto['fecha_fin'])->startOfDay();

                if ($fin->lt($inicio)) {
                    throw ValidationException::withMessages([
                        'conceptos' => 'La fecha final del viatico no puede ser menor a la fecha inicial.',
                    ]);
                }

                $dias = $inicio->diffInDays($fin) + 1;

                $concepto['comprobante_tipo'] = 'viatico';
                $concepto['proveedor'] = null;
                $concepto['rfc'] = null;
                $concepto['uuid'] = null;
                $concepto['sat_cfdi_id'] = null;
                $concepto['fecha'] = $inicio->toDateString();
                $concepto['fecha_inicio'] = $inicio->toDateString();
                $concepto['fecha_fin'] = $fin->toDateString();
                $concepto['dias'] = $dias;
                $concepto['monto'] = round(((float) $concepto['importe_unitario']) * $dias, 2);
            }

            if ($tipoReposicion === 'caja_chica') {
                $concepto['comprobante_tipo'] = 'cfdi';
            }

            if ($tipoReposicion === 'gastos_varios' && !empty($concepto['sat_cfdi_id'])) {
                $concepto['comprobante_tipo'] = 'cfdi';
            }

            if ($tipoReposicion === 'gastos_varios' && empty($concepto['sat_cfdi_id'])) {
                $concepto['comprobante_tipo'] = 'nota';
            }

            return $concepto;
        });

        DB::beginTransaction();

        try {
            $reposicion = ObraReposicionGasto::create([
                'obra_id' => $obra->id,
                'tipo_reposicion' => $tipoReposicion,
                'partida_id' => $request->partida_id,
                'semana' => $request->semana,
                'estatus' => 'solicitado',
                'observaciones' => $request->observaciones,
                'solicitado_por' => auth()->id(),
                'solicitado_at' => now(),
                'total' => $conceptos->sum('monto'),
            ]);

            foreach ($conceptos as $concepto) {
                ObraReposicionGastoDetalle::create([
                    'obra_reposicion_gasto_id' => $reposicion->id,
                    'tipo' => $concepto['tipo'] ?? null,
                    'descripcion' => $concepto['descripcion'] ?? null,
                    'proveedor' => $concepto['proveedor'] ?? null,
                    'rfc' => $concepto['rfc'] ?? null,
                    'uuid' => $concepto['uuid'] ?? null,
                    'fecha' => $concepto['fecha'] ?? null,
                    'fecha_inicio' => $concepto['fecha_inicio'] ?? null,
                    'fecha_fin' => $concepto['fecha_fin'] ?? null,
                    'monto' => $concepto['monto'] ?? 0,
                    'comprobante_tipo' => $concepto['comprobante_tipo'] ?? null,
                    'numero_nota' => $concepto['numero_nota'] ?? null,
                    'dias' => $concepto['dias'] ?? null,
                    'importe_unitario' => $concepto['importe_unitario'] ?? null,
                    'sat_cfdi_id' => $concepto['sat_cfdi_id'] ?? null,
                    'empresa_viatico_tarifa_id' => $concepto['empresa_viatico_tarifa_id'] ?? null,
                    'obra_empleado_id' => $concepto['obra_empleado_id'] ?? null,
                    'partida_id' => $concepto['partida_id'] ?? $request->partida_id,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'reposicion-gastos'])
                ->with('success', 'Reposicion registrada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'reposicion-gastos'])
                ->with('error', 'Error al guardar la reposicion.');
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
        'detalles.obraEmpleado.empleado',
        'detalles.viaticoTarifa',
        'solicitadoPor',
        'revisadoPor',
        'aprobadoPor',
        'pagadoPor',
    ]);

    $seccionesPdf = $this->resolverSeccionesPdf($reposicion);
    $tipo = $seccionesPdf->count() === 1
        ? $seccionesPdf->first()['tipo']
        : 'mixta';

    $view = match ($tipo) {
        'caja_chica' => 'obras.reposicion-gastos.pdf.caja-chica',
        'gastos_varios' => 'obras.reposicion-gastos.pdf.gastos-varios',
        'viaticos' => 'obras.reposicion-gastos.pdf.viaticos',
        'mixta' => 'obras.reposicion-gastos.pdf.mixta',
        default => 'obras.reposicion-gastos.pdf',
    };

    $filenamePrefix = match ($tipo) {
        'caja_chica' => 'reposicion-caja-chica',
        'gastos_varios' => 'reposicion-gastos-varios',
        'viaticos' => 'reposicion-viaticos',
        'mixta' => 'reposicion-gastos-por-tipo',
        default => 'reposicion-gastos',
    };

    $pdf = Pdf::loadView($view, [
        'obra' => $obra,
        'reposicion' => $reposicion,
        'seccionesPdf' => $seccionesPdf,
    ])->setPaper('letter', 'portrait');

    return $pdf->stream($filenamePrefix . '-REP-' . str_pad($reposicion->id, 5, '0', STR_PAD_LEFT) . '.pdf');
}

private function resolverSeccionesPdf(ObraReposicionGasto $reposicion)
{
    $titulos = [
        'caja_chica' => 'REPOSICION CAJA CHICA',
        'gastos_varios' => 'REPOSICION GASTOS VARIOS',
        'viaticos' => 'VIATICOS',
    ];

    $orden = ['caja_chica', 'gastos_varios', 'viaticos'];

    $detallesPorTipo = $reposicion->detalles->groupBy(function (ObraReposicionGastoDetalle $detalle) {
        return $this->resolverTipoDetallePdf($detalle);
    });

    return collect($orden)
        ->filter(fn (string $tipo) => $detallesPorTipo->has($tipo))
        ->map(function (string $tipo) use ($detallesPorTipo, $titulos) {
            $detalles = $detallesPorTipo->get($tipo)->values();

            return [
                'tipo' => $tipo,
                'titulo' => $titulos[$tipo],
                'detalles' => $detalles,
                'total' => $detalles->sum('monto'),
            ];
        })
        ->values();
}

private function resolverTipoDetallePdf(ObraReposicionGastoDetalle $detalle): string
{
    if ($detalle->comprobante_tipo === 'viatico') {
        return 'viaticos';
    }

    $tipo = Str::of($detalle->tipo ?? '')
        ->ascii()
        ->lower()
        ->replace([' ', '-'], '_')
        ->toString();

    return match ($tipo) {
        'caja_chica' => 'caja_chica',
        'gastos_varios' => 'gastos_varios',
        'viaticos' => 'viaticos',
        default => $detalle->comprobante_tipo === 'cfdi' ? 'caja_chica' : 'gastos_varios',
    };
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

