<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\ObraFacturaPago;
use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use Carbon\Carbon;
use App\Services\Facturacion\SatComplementoPagoService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class SatComplementoPagoController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $clienteSearch = trim((string) $request->input('cliente'));
        $estado = $request->input('estado');
        $search = trim((string) $request->input('search'));
        $tab = $request->input('tab') === 'pendientes' ? 'pendientes' : 'registrados';

        $pagosQuery = SatFacturaPago::with(['factura.cliente', 'factura.obra', 'documentos.factura.obra', 'pagosInternosObra'])
            ->when($clienteSearch !== '', function ($query) use ($clienteSearch) {
                $query->whereHas('documentos.factura', function ($facturaQuery) use ($clienteSearch) {
                    $facturaQuery
                        ->where('receptor_nombre', 'like', "%{$clienteSearch}%")
                        ->orWhere('receptor_rfc', 'like', "%{$clienteSearch}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($clienteSearch) {
                            $clienteQuery
                                ->where('razon_social', 'like', "%{$clienteSearch}%")
                                ->orWhere('nombre_comercial', 'like', "%{$clienteSearch}%")
                                ->orWhere('rfc', 'like', "%{$clienteSearch}%");
                        });
                });
            })
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($fechaDesde, fn ($query) => $query->whereDate('fecha_pago', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fecha_pago', '<=', $fechaHasta))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('uuid', 'like', "%{$search}%")
                        ->orWhere('facturapi_response', 'like', "%{$search}%")
                        ->orWhereHas('documentos.factura', function ($facturaQuery) use ($search) {
                            $facturaQuery
                                ->where('uuid', 'like', "%{$search}%")
                                ->orWhere('serie', 'like', "%{$search}%")
                                ->orWhere('folio', 'like', "%{$search}%")
                                ->orWhere('receptor_nombre', 'like', "%{$search}%")
                                ->orWhere('receptor_rfc', 'like', "%{$search}%");
                        });
                });
            });

        $pagos = (clone $pagosQuery)
            ->latest('fecha_pago')
            ->paginate(20)
            ->withQueryString();

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $kpis = [
            'timbrados_mes' => SatFacturaPago::whereIn('estado', ['timbrado', 'cancelacion_pendiente'])
                ->whereBetween('fecha_pago', [$inicioMes, $finMes])
                ->count(),
            'monto_timbrado_mes' => SatFacturaPago::whereIn('estado', ['timbrado', 'cancelacion_pendiente'])
                ->whereBetween('fecha_pago', [$inicioMes, $finMes])
                ->sum('monto'),
        ];

        $facturasPpd = SatFactura::with(['cliente', 'obra'])
            ->withSum([
                'pagos as pagos_timbrados_sum' => fn ($query) => $query->whereIn('estado', ['timbrado', 'cancelacion_pendiente']),
            ], 'sat_factura_pago_documentos.importe_aplicado')
            ->where('metodo_pago', 'PPD')
            ->where('estado', 'timbrada')
            ->when($clienteSearch !== '', function ($query) use ($clienteSearch) {
                $query->where(function ($facturaQuery) use ($clienteSearch) {
                    $facturaQuery
                        ->where('receptor_nombre', 'like', "%{$clienteSearch}%")
                        ->orWhere('receptor_rfc', 'like', "%{$clienteSearch}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($clienteSearch) {
                            $clienteQuery
                                ->where('razon_social', 'like', "%{$clienteSearch}%")
                                ->orWhere('nombre_comercial', 'like', "%{$clienteSearch}%")
                                ->orWhere('rfc', 'like', "%{$clienteSearch}%");
                        });
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('uuid', 'like', "%{$search}%")
                        ->orWhere('serie', 'like', "%{$search}%")
                        ->orWhere('folio', 'like', "%{$search}%")
                        ->orWhere('receptor_nombre', 'like', "%{$search}%")
                        ->orWhere('receptor_rfc', 'like', "%{$search}%");
                });
            })
            ->latest('fecha_emision')
            ->get();

        $facturasPendientes = $facturasPpd->filter(function (SatFactura $factura) {
            $pagado = (float) ($factura->pagos_timbrados_sum ?? 0);
            return round((float) $factura->total - $pagado, 2) > 0;
        });

        $pagosInternosPorUuid = ObraFacturaPago::with(['obra', 'cuentaBanco', 'metodoPago', 'registradoPor'])
            ->where('requiere_complemento_pago', true)
            ->whereNull('sat_factura_pago_id')
            ->whereIn('factura_uuid', $facturasPendientes->pluck('uuid')->filter()->values())
            ->orderByDesc('fecha_pago')
            ->get()
            ->groupBy(fn (ObraFacturaPago $pago) => strtoupper($pago->factura_uuid));

        $facturasPendientes = $facturasPendientes
            ->map(function (SatFactura $factura) use ($pagosInternosPorUuid) {
                $complementado = (float) ($factura->pagos_timbrados_sum ?? 0);
                $saldo = max(0, round((float) $factura->total - $complementado, 2));
                $pagosInternos = $pagosInternosPorUuid->get(strtoupper((string) $factura->uuid), collect());
                $montoInternoPendiente = (float) $pagosInternos->sum('monto');

                $factura->monto_complementado = $complementado;
                $factura->saldo_por_complementar = $saldo;
                $factura->pagos_internos_pendientes = $pagosInternos;
                $factura->monto_interno_pendiente = $montoInternoPendiente;
                $factura->estado_complemento = $complementado <= 0
                    ? 'sin_complemento'
                    : 'parcial';

                return $factura;
            })
            ->values();

        $kpis['facturas_pendientes'] = $facturasPendientes->count();
        $kpis['monto_pendiente'] = $facturasPendientes->sum(function (SatFactura $factura) {
            $pagado = (float) ($factura->pagos_timbrados_sum ?? 0);
            return max(0, round((float) $factura->total - $pagado, 2));
        });

        $estados = SatFacturaPago::query()
            ->select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        return view('sat.complementos-pago.index', compact(
            'pagos',
            'facturasPendientes',
            'kpis',
            'estados',
            'fechaDesde',
            'fechaHasta',
            'clienteSearch',
            'estado',
            'search',
            'tab'
        ));
    }

    public function create(Request $request)
    {
        $request->validate(['factura_id' => ['nullable', 'integer'], 'factura_ids' => ['nullable', 'array', 'max:20'],
            'factura_ids.*' => ['integer', 'distinct']]);
        $facturaSeleccionadaId = $request->integer('factura_id') ?: null;
        $formasPago = collect(config('sat_catalogs.formas_pago', []))->except(['99'])->all();
        $facturasPendientes = $this->facturasPendientesPorComplementar();

        $facturaSeleccionada = $facturasPendientes
            ->firstWhere('id', $facturaSeleccionadaId)
            ?? $facturasPendientes->first();

        $facturasSeleccionadasIds = array_map('intval', $request->input('factura_ids', $facturaSeleccionadaId ? [$facturaSeleccionadaId] : []));

        return view('sat.complementos-pago.create', compact(
            'formasPago',
            'facturasPendientes',
            'facturaSeleccionada',
            'facturasSeleccionadasIds'
        ));
    }

    public function store(Request $request, SatComplementoPagoService $servicio)
    {
        // Compatibilidad con la captura anterior de una sola factura.
        if (! $request->has('facturas') && $request->filled('factura_id')) {
            $request->merge(['facturas' => [['factura_id' => $request->input('factura_id'), 'monto' => $request->input('monto')]]]);
        }
        $data = $request->validate([
            'solicitud_id' => ['required', 'uuid'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:now'],
            'forma_pago' => ['required', Rule::in(array_values(array_diff(array_map('strval', array_keys(config('sat_catalogs.formas_pago', []))), ['99'])))],
            'tipo_cambio' => ['nullable', 'numeric', 'gt:0', 'decimal:0,6'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'],
            'facturas' => ['required', 'array', 'min:1', 'max:20'],
            'facturas.*.factura_id' => ['required', 'integer', 'distinct', 'exists:sat_facturas,id'],
            'facturas.*.monto' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'],
        ]);
        try {
            $pago = $servicio->generar($data, (int) $request->user()->id);
        } catch (ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            report($error);
            return back()->withInput()->with('error', 'No se pudo generar el complemento: '.$error->getMessage());
        }

        return redirect()->route('sat.facturacion.pagos.show', $pago)->with(
            $pago->estado === 'timbrado' ? 'success' : 'warning',
            $pago->estado === 'timbrado' ? 'Complemento generado: un solo CFDI para '.$pago->documentos()->count().' factura(s).'
                : 'La solicitud quedó guardada con estado '.$pago->estado.'. Consulta o reintenta este mismo complemento; no generes otro.'
        );
    }

    public function reintentar(Request $request, SatFacturaPago $pago, SatComplementoPagoService $servicio)
    {
        if (! $pago->solicitud_id || ! in_array($pago->estado, ['en_proceso', 'pendiente_confirmacion'], true)) {
            return back()->with('error', 'Este complemento no tiene una emisión pendiente de confirmar.');
        }
        // Se recuperan las entradas del pago original; no se acepta otro reparto.
        $facturas = $pago->documentos()->orderBy('sat_factura_id')->get()->map(fn ($documento) => [
            'factura_id' => $documento->sat_factura_id, 'monto' => $documento->importe_aplicado,
        ])->all();
        try {
            $resultado = $servicio->generar(['solicitud_id' => $pago->solicitud_id,
                'fecha_pago' => $pago->fecha_pago->toIso8601String(), 'forma_pago' => $pago->forma_pago,
                'tipo_cambio' => $pago->tipo_cambio, 'monto' => $pago->monto, 'facturas' => $facturas], (int) $request->user()->id);
        } catch (\Throwable $error) {
            return back()->with('error', $error->getMessage());
        }

        return back()->with($resultado->estado === 'timbrado' ? 'success' : 'warning',
            $resultado->estado === 'timbrado' ? 'Timbrado confirmado. Se conserva el mismo CFDI.' : 'El timbrado sigue pendiente de confirmar. No generes otro complemento.');
    }

    private function facturasPendientesPorComplementar()
    {
        $facturasPpd = SatFactura::with(['cliente', 'obra'])
            ->withSum([
                'pagos as pagos_timbrados_sum' => fn ($query) => $query->whereIn('estado', ['timbrado', 'cancelacion_pendiente']),
            ], 'sat_factura_pago_documentos.importe_aplicado')
            ->where('metodo_pago', 'PPD')
            ->where('estado', 'timbrada')
            ->latest('fecha_emision')
            ->get();

        $facturasPendientes = $facturasPpd->filter(function (SatFactura $factura) {
            $pagado = (float) ($factura->pagos_timbrados_sum ?? 0);
            return round((float) $factura->total - $pagado, 2) > 0;
        });

        $pagosInternosPorUuid = ObraFacturaPago::with(['obra', 'cuentaBanco', 'metodoPago', 'registradoPor'])
            ->where('requiere_complemento_pago', true)
            ->whereNull('sat_factura_pago_id')
            ->whereIn('factura_uuid', $facturasPendientes->pluck('uuid')->filter()->values())
            ->orderByDesc('fecha_pago')
            ->get()
            ->groupBy(fn (ObraFacturaPago $pago) => strtoupper($pago->factura_uuid));

        return $facturasPendientes
            ->map(function (SatFactura $factura) use ($pagosInternosPorUuid) {
                $complementado = (float) ($factura->pagos_timbrados_sum ?? 0);
                $saldo = max(0, round((float) $factura->total - $complementado, 2));
                $pagosInternos = $pagosInternosPorUuid->get(strtoupper((string) $factura->uuid), collect());
                $montoInternoPendiente = (float) $pagosInternos->sum('monto');

                $factura->monto_complementado = $complementado;
                $factura->saldo_por_complementar = $saldo;
                $factura->pagos_internos_pendientes = $pagosInternos;
                $factura->monto_interno_pendiente = $montoInternoPendiente;
                $factura->estado_complemento = $complementado <= 0
                    ? 'sin_complemento'
                    : 'parcial';

                return $factura;
            })
            ->values();
    }
}


