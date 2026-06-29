<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\ObraFacturaPago;
use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SatComplementoPagoController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $clienteSearch = trim((string) $request->input('cliente'));
        $estado = $request->input('estado');
        $search = trim((string) $request->input('search'));

        $pagosQuery = SatFacturaPago::with(['factura.cliente', 'factura.obra', 'pagosInternosObra'])
            ->when($clienteSearch !== '', function ($query) use ($clienteSearch) {
                $query->whereHas('factura', function ($facturaQuery) use ($clienteSearch) {
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
                        ->orWhereHas('factura', function ($facturaQuery) use ($search) {
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
            'timbrados_mes' => SatFacturaPago::where('estado', 'timbrado')
                ->whereBetween('fecha_pago', [$inicioMes, $finMes])
                ->count(),
            'monto_timbrado_mes' => SatFacturaPago::where('estado', 'timbrado')
                ->whereBetween('fecha_pago', [$inicioMes, $finMes])
                ->sum('monto'),
        ];

        $facturasPpd = SatFactura::with(['cliente', 'obra'])
            ->withSum([
                'pagos as pagos_timbrados_sum' => fn ($query) => $query->where('estado', 'timbrado'),
            ], 'monto')
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
            'search'
        ));
    }

    public function create(Request $request)
    {
        $facturaSeleccionadaId = $request->integer('factura_id') ?: null;
        $formasPago = config('sat_catalogs.formas_pago', []);
        $facturasPendientes = $this->facturasPendientesPorComplementar();

        $facturaSeleccionada = $facturasPendientes
            ->firstWhere('id', $facturaSeleccionadaId)
            ?? $facturasPendientes->first();

        return view('sat.complementos-pago.create', compact(
            'formasPago',
            'facturasPendientes',
            'facturaSeleccionada'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'factura_id' => ['required', 'exists:sat_facturas,id'],
            'fecha_pago' => ['required', 'date'],
            'forma_pago' => ['required', 'string', 'max:5'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        $factura = SatFactura::with('pagos')->findOrFail($data['factura_id']);

        if ($factura->estado !== 'timbrada') {
            return back()->withInput()->with('error', 'Solo se pueden registrar pagos a facturas timbradas.');
        }

        if ($factura->metodo_pago !== 'PPD') {
            return back()->withInput()->with('error', 'Solo las facturas PPD pueden generar complemento de pago.');
        }

        if (! $factura->facturapi_invoice_id || ! $factura->uuid || ! $factura->facturapi_customer_id) {
            return back()->withInput()->with('error', 'La factura no tiene UUID, cliente o ID de Facturapi.');
        }

        $totalPagado = $factura->pagos()
            ->whereIn('estado', ['timbrado', 'registrado'])
            ->sum('monto');

        $saldoAnterior = round((float) $factura->total - (float) $totalPagado, 2);
        $monto = round((float) $data['monto'], 2);
        $saldoInsoluto = round($saldoAnterior - $monto, 2);

        if ($saldoAnterior <= 0) {
            return back()->withInput()->with('error', 'La factura ya no tiene saldo pendiente por complementar.');
        }

        if ($monto > $saldoAnterior) {
            return back()->withInput()->with('error', 'El monto pagado no puede ser mayor al saldo pendiente.');
        }

        $numeroParcialidad = $factura->pagos()
            ->whereIn('estado', ['timbrado', 'registrado'])
            ->count() + 1;

        $payload = [
            'type' => 'P',
            'customer' => $factura->facturapi_customer_id,
            'complements' => [
                [
                    'type' => 'pago',
                    'data' => [
                        [
                            'payment_form' => $data['forma_pago'],
                            'currency' => $factura->moneda ?? 'MXN',
                            'exchange' => (float) ($factura->tipo_cambio ?: 1),
                            'date' => Carbon::parse($data['fecha_pago'])->toIso8601String(),
                            'related_documents' => [
                                [
                                    'uuid' => $factura->uuid,
                                    'amount' => $monto,
                                    'installment' => $numeroParcialidad,
                                    'last_balance' => $saldoAnterior,
                                    'taxes' => [
                                        [
                                            'type' => 'IVA',
                                            'rate' => 0.16,
                                            'base' => round($monto / 1.16, 2),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
                ->post('https://www.facturapi.io/v2/invoices', $payload);

            if (! $response->successful()) {
                $error = $response->json('message')
                    ?? $response->json('error')
                    ?? $response->body();

                return back()->withInput()->with('error', 'Error al generar complemento de pago: ' . $error);
            }

            $body = $response->json();

            $pago = SatFacturaPago::create([
                'sat_factura_id' => $factura->id,
                'facturapi_invoice_id' => $body['id'] ?? null,
                'uuid' => data_get($body, 'uuid') ?? data_get($body, 'stamp.uuid'),
                'fecha_pago' => $data['fecha_pago'],
                'forma_pago' => $data['forma_pago'],
                'moneda' => $factura->moneda ?? 'MXN',
                'tipo_cambio' => $factura->tipo_cambio,
                'monto' => $monto,
                'saldo_anterior' => $saldoAnterior,
                'saldo_insoluto' => $saldoInsoluto,
                'numero_parcialidad' => $numeroParcialidad,
                'estado' => 'timbrado',
                'facturapi_response' => $body,
                'error_message' => null,
            ]);

            if (! empty($body['id'])) {
                $this->guardarArchivosPago($pago);
            }

            $pagosInternosLigados = $this->ligarPagosInternos($factura, $pago, $monto);
            $mensaje = 'Complemento de pago generado correctamente.';

            if ($pagosInternosLigados > 0) {
                $mensaje .= " Se ligaron {$pagosInternosLigados} pago(s) interno(s).";
            }

            return redirect()
                ->route('sat.complementos-pago.index')
                ->with('success', $mensaje);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error al generar complemento de pago: ' . $e->getMessage());
        }
    }

    private function ligarPagosInternos(SatFactura $factura, SatFacturaPago $pago, float $monto): int
    {
        $pagosInternos = ObraFacturaPago::where('requiere_complemento_pago', true)
            ->whereNull('sat_factura_pago_id')
            ->where('factura_uuid', $factura->uuid)
            ->orderBy('fecha_pago')
            ->orderBy('id')
            ->get();

        if ($pagosInternos->isEmpty()) {
            return 0;
        }

        $seleccionados = collect();
        $acumulado = 0.0;

        foreach ($pagosInternos as $pagoInterno) {
            $acumulado = round($acumulado + (float) $pagoInterno->monto, 2);
            $seleccionados->push($pagoInterno);

            if ($acumulado >= $monto) {
                break;
            }
        }

        if (round($acumulado, 2) !== round($monto, 2)) {
            return 0;
        }

        ObraFacturaPago::whereIn('id', $seleccionados->pluck('id'))
            ->update(['sat_factura_pago_id' => $pago->id]);

        return $seleccionados->count();
    }

    private function guardarArchivosPago(SatFacturaPago $pago): void
    {
        $basePath = 'sat/facturas/pagos/' . $pago->id;

        $xmlResponse = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->get('https://www.facturapi.io/v2/invoices/' . $pago->facturapi_invoice_id . '/xml');

        if ($xmlResponse->successful()) {
            $xmlPath = $basePath . '/pago-' . $pago->id . '.xml';

            Storage::disk('local')->put($xmlPath, $xmlResponse->body());

            $pago->update([
                'xml_path' => $xmlPath,
            ]);
        }

        $pdfResponse = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->get('https://www.facturapi.io/v2/invoices/' . $pago->facturapi_invoice_id . '/pdf');

        if ($pdfResponse->successful()) {
            $pdfPath = $basePath . '/pago-' . $pago->id . '.pdf';

            Storage::disk('local')->put($pdfPath, $pdfResponse->body());

            $pago->update([
                'pdf_path' => $pdfPath,
            ]);
        }
    }

    private function facturasPendientesPorComplementar()
    {
        $facturasPpd = SatFactura::with(['cliente', 'obra'])
            ->withSum([
                'pagos as pagos_timbrados_sum' => fn ($query) => $query->where('estado', 'timbrado'),
            ], 'monto')
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
