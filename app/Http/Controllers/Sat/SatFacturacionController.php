<?php
namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SatFactura;
use App\Models\SatEmpresa;
use App\Models\SatFacturaConcepto;
use App\Models\SatFacturaPago;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\SatConcepto;
use App\Models\SatCfdi;
use App\Models\SatFacturaBorrador;
use App\Models\ObraFacturaBorrador;
use App\Services\Facturacion\FacturapiService;
use Facturapi\Exceptions\FacturapiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Arr;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;


use App\Mail\SatFacturaMail;
use Illuminate\Support\Facades\Mail;



use Carbon\Carbon;
use ZipArchive;

class SatFacturacionController extends Controller
{
    /**
     * Listado de facturas emitidas.
     */
   public function index(Request $request)
    {
        $facturasTimbradas = SatFactura::with(['cliente', 'obra', 'ordenCompra', 'empresa'])
            ->latest()
            ->get();

        $borradoresCfdi = SatFacturaBorrador::with(['cliente', 'obra', 'empresa'])
            ->where('estado', 'borrador')
            ->latest()
            ->get();

        $items = $facturasTimbradas
            ->concat($borradoresCfdi)
            ->sortByDesc(fn ($item) => $item->updated_at ?? $item->created_at)
            ->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $facturas = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $totalFacturado = SatFactura::where('estado', 'timbrada')->sum('total');

        $timbradas = SatFactura::where('estado', 'timbrada')->count();
        $pendientes = SatFactura::where('estado', 'borrador')->count() + SatFacturaBorrador::where('estado', 'borrador')->count();
        $canceladas = SatFactura::where('estado', 'cancelada')->count();

        return view('sat.facturacion.index', compact(
            'facturas',
            'totalFacturado',
            'timbradas',
            'pendientes',
            'canceladas'
        ));
    }

    public function clienteResumen(Cliente $cliente)
    {
        $facturasVigentes = SatFactura::with(['empresa', 'obra'])
            ->where('cliente_id', $cliente->id)
            ->whereIn('estado', ['timbrada', 'cancelacion_solicitada'])
            ->latest('fecha_emision')
            ->get();

        $facturasCanceladas = SatFactura::with(['empresa', 'obra'])
            ->where('cliente_id', $cliente->id)
            ->where('estado', 'cancelada')
            ->latest('fecha_cancelacion')
            ->latest('fecha_emision')
            ->get();

        $pagos = SatFacturaPago::with('factura')
            ->whereHas('factura', fn ($query) => $query->where('cliente_id', $cliente->id))
            ->latest('fecha_pago')
            ->get();

        $totalVigente = $facturasVigentes->sum('total');
        $totalCancelado = $facturasCanceladas->sum('total');
        $totalPagado = $pagos->where('estado', 'timbrado')->sum('monto');

        return view('sat.facturacion.cliente', compact(
            'cliente',
            'facturasVigentes',
            'facturasCanceladas',
            'pagos',
            'totalVigente',
            'totalCancelado',
            'totalPagado'
        ));
    }

    /**
     * Formulario para nueva factura.
     */
  public function create(Request $request)
{
    $empresas = SatEmpresa::where('activo', true)
        ->orderBy('nombre')
        ->get();

    $clientes = Cliente::where('activo', true)
        ->orderBy('razon_social')
        ->orderBy('nombre_comercial')
        ->get();

    $obras = Obra::orderBy('nombre')->get();

    // 👇 NUEVO
    $conceptos = SatConcepto::where('activo', true)
        ->orderBy('descripcion')
        ->get();

    $borrador = null;
    $cfdiBorrador = null;
    $prefill = [];

    if ($request->filled('cfdi_borrador_id')) {
        $cfdiBorrador = SatFacturaBorrador::with(['cliente', 'obra', 'empresa'])
            ->where('estado', 'borrador')
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            })
            ->findOrFail($request->integer('cfdi_borrador_id'));

        $prefill = $this->prefillFromCfdiBorrador($cfdiBorrador);
    }

    if ($request->filled('borrador_id')) {
        abort_unless(auth()->user()?->can('obra_factura_borradores.invoice.access'), 403);

        $borrador = ObraFacturaBorrador::with(['obra', 'cliente', 'conceptoSat'])
            ->findOrFail($request->integer('borrador_id'));

        if (
            $borrador->estatus !== ObraFacturaBorrador::ESTATUS_AUTORIZADO
            || $borrador->sat_factura_id
        ) {
            return redirect()
                ->route('obras.factura-borradores.show', [$borrador->obra_id, $borrador->id])
                ->with('error', 'Solo se pueden facturar borradores autorizados y sin factura ligada.');
        }

        $cantidad = max((float) $borrador->cantidad, 0.000001);
        $precioUnitario = round((float) $borrador->subtotal / $cantidad, 2);
        $ivaTasa = (float) $borrador->subtotal > 0
            ? round((float) $borrador->iva / (float) $borrador->subtotal, 2)
            : 0.16;

        $prefill = [
            'obra_factura_borrador_id' => (string) $borrador->id,
            'cliente_id' => (string) $borrador->cliente_id,
            'obra_id' => (string) $borrador->obra_id,
            'uso_cfdi' => $borrador->uso_cfdi ?: 'G03',
            'metodo_pago' => $borrador->metodo_pago ?: 'PUE',
            'forma_pago' => $borrador->forma_pago ?: '03',
            'tipo_iva' => in_array((string) $ivaTasa, ['0.16', '0.08', '0'], true)
                ? (string) $ivaTasa
                : ((float) $borrador->iva > 0 ? '0.16' : '0'),
            'amortizacion' => 0,
            'descuento' => (float) $borrador->descuentos,
            'retenciones' => (float) $borrador->retenciones,
            'conceptos' => [[
                'id' => $borrador->sat_concepto_id,
                'codigo' => $borrador->conceptoSat?->codigo,
                'nombre_catalogo' => $borrador->conceptoSat?->descripcion ?: $borrador->concepto_descripcion,
                'descripcion' => $borrador->concepto_descripcion,
                'clave_producto_servicio' => $borrador->conceptoSat?->clave_producto_servicio ?: '84111506',
                'clave_unidad' => $borrador->conceptoSat?->clave_unidad ?: 'ACT',
                'unidad' => $borrador->conceptoSat?->unidad ?: 'Actividad',
                'objeto_impuesto' => $borrador->conceptoSat?->objeto_impuesto ?: '02',
                'iva_tasa' => $ivaTasa,
                'incluye_iva' => false,
                'cantidad' => (float) $borrador->cantidad,
                'precio_unitario' => $precioUnitario,
            ]],
        ];
    }

    $cfdiBorradores = SatFacturaBorrador::with(['cliente', 'obra', 'empresa'])
        ->where('estado', 'borrador')
        ->where(function ($query) {
            $query->whereNull('user_id')
                ->orWhere('user_id', auth()->id());
        })
        ->latest()
        ->limit(8)
        ->get();

    return view('sat.facturacion.create', compact(
        'empresas',
        'clientes',
        'obras',
        'conceptos',
        'borrador',
        'cfdiBorrador',
        'cfdiBorradores',
        'prefill'
    ));
}

public function storeBorrador(Request $request)
{
    $payload = $this->normalizarPayloadBorradorCfdi($request);
    $titulo = $this->tituloBorradorCfdi($payload);

    $borrador = null;

    if ($request->filled('cfdi_borrador_id')) {
        $borrador = SatFacturaBorrador::where('estado', 'borrador')
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            })
            ->find($request->integer('cfdi_borrador_id'));
    }

    $attributes = [
        'user_id' => auth()->id(),
        'sat_empresa_id' => $payload['sat_empresa_id'] ?: null,
        'cliente_id' => $payload['cliente_id'] ?: null,
        'obra_id' => $payload['obra_id'] ?: null,
        'obra_factura_borrador_id' => $payload['obra_factura_borrador_id'] ?: null,
        'titulo' => $titulo,
        'payload' => $payload,
        'estado' => 'borrador',
    ];

    if ($borrador) {
        $borrador->update($attributes);
    } else {
        $borrador = SatFacturaBorrador::create($attributes);
    }

    return redirect()
        ->route('sat.facturacion.create', ['cfdi_borrador_id' => $borrador->id])
        ->with('success', 'Borrador CFDI guardado correctamente.');
}

private function normalizarPayloadBorradorCfdi(Request $request): array
{
    $conceptos = collect($request->input('conceptos', []))
        ->filter(fn ($concepto) => is_array($concepto) && trim((string) ($concepto['descripcion'] ?? '')) !== '')
        ->map(fn ($concepto) => [
            'id' => $concepto['sat_concepto_id'] ?? $concepto['id'] ?? null,
            'sat_concepto_id' => $concepto['sat_concepto_id'] ?? $concepto['id'] ?? null,
            'codigo' => $concepto['codigo'] ?? '',
            'nombre_catalogo' => $concepto['nombre_catalogo'] ?? $concepto['descripcion'] ?? '',
            'descripcion' => $concepto['descripcion'] ?? '',
            'clave_producto_servicio' => $concepto['clave_producto_servicio'] ?? '',
            'clave_unidad' => $concepto['clave_unidad'] ?? '',
            'unidad' => $concepto['unidad'] ?? '',
            'objeto_impuesto' => $concepto['objeto_impuesto'] ?? '02',
            'iva_tasa' => (float) ($concepto['iva_tasa'] ?? 0),
            'incluye_iva' => filter_var($concepto['incluye_iva'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'cantidad' => (float) ($concepto['cantidad'] ?? 1),
            'precio_unitario' => (float) ($concepto['precio_unitario'] ?? 0),
        ])
        ->values()
        ->all();

    return [
        'sat_empresa_id' => $request->input('sat_empresa_id'),
        'cliente_id' => $request->input('cliente_id'),
        'obra_id' => $request->input('obra_id'),
        'obra_factura_borrador_id' => $request->input('obra_factura_borrador_id'),
        'uso_cfdi' => $request->input('uso_cfdi', 'G03'),
        'metodo_pago' => $request->input('metodo_pago', 'PUE'),
        'forma_pago' => $request->input('forma_pago', '03'),
        'tipo_iva' => $request->input('tipo_iva', '0.16'),
        'amortizacion' => (float) $request->input('amortizacion', 0),
        'descuento' => (float) $request->input('descuento', 0),
        'retenciones' => (float) $request->input('retenciones', 0),
        'usar_relacion' => $request->boolean('usar_relacion'),
        'relacion_tipo' => $request->input('relacion_tipo'),
        'relacion_uuids' => $request->input('relacion_uuids'),
        'usar_complemento_construccion' => $request->boolean('usar_complemento_construccion'),
        'complemento_construccion' => $request->input('complemento_construccion', []),
        'conceptos' => $conceptos,
    ];
}

private function prefillFromCfdiBorrador(SatFacturaBorrador $borrador): array
{
    $payload = $borrador->payload ?: [];

    return [
        'cfdi_borrador_id' => (string) $borrador->id,
        'sat_empresa_id' => (string) ($payload['sat_empresa_id'] ?? ''),
        'cliente_id' => (string) ($payload['cliente_id'] ?? ''),
        'obra_id' => (string) ($payload['obra_id'] ?? ''),
        'obra_factura_borrador_id' => (string) ($payload['obra_factura_borrador_id'] ?? ''),
        'uso_cfdi' => $payload['uso_cfdi'] ?? 'G03',
        'metodo_pago' => $payload['metodo_pago'] ?? 'PUE',
        'forma_pago' => $payload['forma_pago'] ?? '03',
        'tipo_iva' => $payload['tipo_iva'] ?? '0.16',
        'amortizacion' => (float) ($payload['amortizacion'] ?? 0),
        'descuento' => (float) ($payload['descuento'] ?? 0),
        'retenciones' => (float) ($payload['retenciones'] ?? 0),
        'usar_relacion' => (bool) ($payload['usar_relacion'] ?? false),
        'relacion_tipo' => $payload['relacion_tipo'] ?? null,
        'relacion_uuids' => $payload['relacion_uuids'] ?? '',
        'usar_complemento_construccion' => (bool) ($payload['usar_complemento_construccion'] ?? false),
        'complemento_construccion' => $payload['complemento_construccion'] ?? [],
        'conceptos' => $payload['conceptos'] ?? [],
    ];
}

private function tituloBorradorCfdi(array $payload): string
{
    $cliente = !empty($payload['cliente_id']) ? Cliente::find($payload['cliente_id']) : null;
    $obra = !empty($payload['obra_id']) ? Obra::find($payload['obra_id']) : null;
    $concepto = Arr::get($payload, 'conceptos.0.descripcion', 'Sin conceptos');

    return collect([
        $cliente?->razon_social ?: $cliente?->nombre_comercial,
        $obra?->nombre ?: $obra?->Nombre,
        str($concepto)->limit(50)->toString(),
    ])->filter()->join(' - ') ?: 'Borrador CFDI';
}
public function relacionables(Request $request)
{
    $data = $request->validate([
        'q' => ['nullable', 'string', 'max:120'],
        'cliente_id' => ['nullable', 'exists:clientes,id'],
        'sat_empresa_id' => ['nullable', 'exists:sat_empresas,id'],
    ]);

    $q = trim((string) ($data['q'] ?? ''));
    $cliente = !empty($data['cliente_id']) ? Cliente::find($data['cliente_id']) : null;
    $empresa = !empty($data['sat_empresa_id']) ? SatEmpresa::find($data['sat_empresa_id']) : null;
    $clienteRfc = $cliente ? $this->normalizeRfc($cliente->rfc) : null;
    $empresaRfc = $empresa ? $this->normalizeRfc($empresa->rfc) : null;

    $facturas = SatFactura::query()
        ->with(['empresa', 'cliente'])
        ->whereNotNull('uuid')
        ->where('uuid', '!=', '')
        ->whereIn('estado', ['timbrada', 'cancelacion_solicitada'])
        ->when($cliente, fn ($query) => $query->where('cliente_id', $cliente->id))
        ->when($empresa, fn ($query) => $query->where('sat_empresa_id', $empresa->id))
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($subquery) use ($q) {
                $subquery
                    ->where('uuid', 'like', "%{$q}%")
                    ->orWhere('serie', 'like', "%{$q}%")
                    ->orWhere('folio', 'like', "%{$q}%")
                    ->orWhere('receptor_rfc', 'like', "%{$q}%")
                    ->orWhere('receptor_nombre', 'like', "%{$q}%");
            });
        })
        ->latest('fecha_emision')
        ->limit(30)
        ->get()
        ->map(fn (SatFactura $factura) => [
            'source' => 'sat_facturas',
            'source_label' => 'Facturapi',
            'id' => $factura->id,
            'uuid' => $factura->uuid,
            'serie' => $factura->serie,
            'folio' => $factura->folio,
            'fecha' => optional($factura->fecha_emision)->format('Y-m-d'),
            'fecha_formateada' => optional($factura->fecha_emision)->format('d/m/Y'),
            'emisor_rfc' => $factura->empresa?->rfc,
            'emisor_nombre' => $factura->empresa?->nombre,
            'receptor_rfc' => $factura->receptor_rfc,
            'receptor_nombre' => $factura->receptor_nombre,
            'tipo_comprobante' => $factura->tipo_comprobante,
            'subtotal' => (float) $factura->subtotal,
            'total' => (float) $factura->total,
            'moneda' => $factura->moneda ?? 'MXN',
            'estado' => $factura->estado,
        ]);

    $cfdis = SatCfdi::query()
        ->whereNotNull('uuid')
        ->where('uuid', '!=', '')
        ->when($clienteRfc, function ($query) use ($clienteRfc) {
            $query->where(function ($subquery) use ($clienteRfc) {
                $subquery
                    ->where('receptor_rfc', $clienteRfc)
                    ->orWhere('rfc_receptor', $clienteRfc);
            });
        })
        ->when($empresaRfc, function ($query) use ($empresaRfc) {
            $query->where(function ($subquery) use ($empresaRfc) {
                $subquery
                    ->where('rfc_emisor', $empresaRfc)
                    ->orWhere('emisor_rfc', $empresaRfc);
            });
        })
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($subquery) use ($q) {
                $subquery
                    ->where('uuid', 'like', "%{$q}%")
                    ->orWhere('serie', 'like', "%{$q}%")
                    ->orWhere('folio', 'like', "%{$q}%")
                    ->orWhere('receptor_rfc', 'like', "%{$q}%")
                    ->orWhere('receptor_nombre', 'like', "%{$q}%")
                    ->orWhere('emisor_rfc', 'like', "%{$q}%")
                    ->orWhere('emisor_nombre', 'like', "%{$q}%");
            });
        })
        ->latest('fecha_emision')
        ->limit(30)
        ->get()
        ->map(fn (SatCfdi $cfdi) => [
            'source' => 'sat_cfdis',
            'source_label' => 'SAT',
            'id' => $cfdi->id,
            'uuid' => $cfdi->uuid,
            'serie' => $cfdi->serie,
            'folio' => $cfdi->folio,
            'fecha' => optional($cfdi->fecha_emision)->format('Y-m-d'),
            'fecha_formateada' => optional($cfdi->fecha_emision)->format('d/m/Y'),
            'emisor_rfc' => $cfdi->emisor_rfc ?: $cfdi->rfc_emisor,
            'emisor_nombre' => $cfdi->emisor_nombre,
            'receptor_rfc' => $cfdi->receptor_rfc ?: $cfdi->rfc_receptor,
            'receptor_nombre' => $cfdi->receptor_nombre,
            'tipo_comprobante' => $cfdi->tipo_comprobante,
            'subtotal' => (float) $cfdi->subtotal,
            'total' => (float) $cfdi->total,
            'moneda' => $cfdi->moneda ?? 'MXN',
            'estado' => null,
        ]);

    $items = $facturas
        ->concat($cfdis)
        ->filter(fn ($item) => !empty($item['uuid']))
        ->unique(fn ($item) => strtoupper($item['uuid']))
        ->sortByDesc(fn ($item) => $item['fecha'] ?? '')
        ->take(30)
        ->values();

    return response()->json([
        'ok' => true,
        'data' => $items,
    ]);
}

public function preview(Request $request, FacturapiService $facturapiService)
{
    $data = $request->validate([
        'sat_empresa_id' => ['required', 'exists:sat_empresas,id'],
        'cliente_id' => ['required', 'exists:clientes,id'],
        'obra_id' => ['nullable', 'exists:obras,id'],

        'uso_cfdi' => ['required', 'string', 'max:10'],
        'metodo_pago' => ['required', 'string', 'max:10'],
        'forma_pago' => ['nullable', 'string', 'max:10'],
        'tipo_iva' => ['required', 'in:0.16,0.08,0,exento,sin_iva'],
        'amortizacion' => ['nullable', 'numeric', 'min:0'],
        'descuento' => ['nullable', 'numeric', 'min:0'],
        'retenciones' => ['nullable', 'numeric', 'min:0'],

        'conceptos' => ['required', 'array', 'min:1'],
        'conceptos.*.descripcion' => ['required', 'string', 'max:255'],
        'conceptos.*.clave_producto_servicio' => ['required', 'string', 'max:20'],
        'conceptos.*.clave_unidad' => ['required', 'string', 'max:20'],
        'conceptos.*.unidad' => ['nullable', 'string', 'max:100'],
        'conceptos.*.cantidad' => ['required', 'numeric', 'min:0.000001'],
        'conceptos.*.precio_unitario' => ['required', 'numeric', 'min:0'],

        'usar_relacion' => ['nullable'],
        'relacion_tipo' => ['required_if:usar_relacion,1', 'nullable', 'string', 'max:2'],
        'relacion_uuids' => ['required_if:usar_relacion,1', 'nullable', 'string'],

        'usar_complemento_construccion' => ['nullable'],
        'complemento_construccion' => ['nullable', 'array'],
        'complemento_construccion.num_per_lico_aut' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:50'],
        'complemento_construccion.calle' => ['nullable', 'string', 'max:255'],
        'complemento_construccion.no_exterior' => ['nullable', 'string', 'max:50'],
        'complemento_construccion.no_interior' => ['nullable', 'string', 'max:50'],
        'complemento_construccion.colonia' => ['nullable', 'string', 'max:100'],
        'complemento_construccion.localidad' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:100'],
        'complemento_construccion.referencia' => ['nullable', 'string', 'max:255'],
        'complemento_construccion.municipio' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:100'],
        'complemento_construccion.estado' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:2'],
        'complemento_construccion.codigo_postal' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:5'],
    ]);

    $cliente = Cliente::findOrFail($data['cliente_id']);

    if (!$cliente->regimen_fiscal) {
        throw new \RuntimeException('El cliente no tiene regimen fiscal configurado.');
    }

    if (!$cliente->codigo_postal) {
        throw new \RuntimeException('El cliente no tiene codigo postal fiscal configurado.');
    }

    try {
        $rfc = $this->normalizeRfc($cliente->rfc);

        if (!$rfc) {
            throw new \RuntimeException('El cliente no tiene RFC fiscal configurado.');
        }

        $payload = $this->buildFacturapiPreviewPayload($request, $data, $cliente, $rfc);
        $pdf = $facturapiService->client()->Invoices->previewPdf($payload);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-factura-cfdi.pdf"',
        ]);
    } catch (FacturapiException $e) {
        $rfc = isset($rfc) && $rfc ? " RFC enviado: {$rfc}." : '';

        return back()
            ->withInput()
            ->with('error', 'Error al generar previsualizacion CFDI: ' . $e->getMessage() . $rfc);
    } catch (\Throwable $e) {
        return back()
            ->withInput()
            ->with('error', 'Error al generar previsualizacion CFDI: ' . $e->getMessage());
    }
}

private function normalizeRfc(?string $rfc): string
{
    return preg_replace('/[^A-Z0-9&Ñ]/u', '', strtoupper(trim((string) $rfc))) ?? '';
}

private function buildFacturapiPreviewPayload(Request $request, array $data, Cliente $cliente, string $rfc): array
{
    $tipoIva = $data['tipo_iva'];
    $ivaTasaNum = match (true) {
        in_array($tipoIva, ['0.16', '0.08'], true) => (float) $tipoIva,
        default => 0.0,
    };

    $items = [];
    $subtotalBase = collect($data['conceptos'])->sum(function ($concepto) {
        return (float) $concepto['cantidad'] * (float) $concepto['precio_unitario'];
    });
    $descuentoGlobal = round((float) ($data['descuento'] ?? 0) + (float) ($data['amortizacion'] ?? 0), 2);
    $descuentoAplicado = 0.0;
    $conceptosCount = count($data['conceptos']);

    foreach ($data['conceptos'] as $index => $concepto) {
        $lineSubtotal = (float) $concepto['cantidad'] * (float) $concepto['precio_unitario'];
        $lineDiscount = 0.0;

        if ($descuentoGlobal > 0 && $subtotalBase > 0) {
            $lineDiscount = $index === $conceptosCount - 1
                ? round($descuentoGlobal - $descuentoAplicado, 2)
                : round($descuentoGlobal * ($lineSubtotal / $subtotalBase), 2);
            $descuentoAplicado += $lineDiscount;
        }

        $product = [
            'description' => $concepto['descripcion'],
            'product_key' => $concepto['clave_producto_servicio'],
            'unit_key' => $concepto['clave_unidad'] ?: 'H87',
            'unit_name' => $concepto['unidad'] ?: 'Pieza',
            'price' => (float) $concepto['precio_unitario'],
            'tax_included' => false,
            'taxability' => $tipoIva === 'sin_iva' ? '01' : '02',
        ];

        $product['taxes'] = match (true) {
            $tipoIva === 'exento' => [['type' => 'IVA', 'factor' => 'Exento']],
            $tipoIva === '0' => [['type' => 'IVA', 'rate' => 0.0, 'factor' => 'Tasa']],
            in_array($tipoIva, ['0.16', '0.08'], true) => [['type' => 'IVA', 'rate' => $ivaTasaNum]],
            default => [],
        };

        $items[] = [
            'quantity' => (float) $concepto['cantidad'],
            'discount' => $lineDiscount,
            'product' => $product,
        ];
    }

    $payload = [
        'customer' => [
            'legal_name' => $cliente->razon_social ?: $cliente->nombre_comercial,
            'tax_id' => $rfc,
            'tax_system' => $cliente->regimen_fiscal,
            'email' => $cliente->email ?: 'facturacion@example.com',
            'address' => [
                'zip' => $cliente->codigo_postal,
            ],
        ],
        'items' => $items,
        'payment_form' => $data['forma_pago'] ?? '03',
        'payment_method' => $data['metodo_pago'],
        'use' => $data['uso_cfdi'],
    ];

    if ($request->boolean('usar_relacion') && !empty($data['relacion_uuids'])) {
        $uuids = array_values(array_filter(array_map('trim', explode(',', $data['relacion_uuids']))));

        if ($uuids) {
            $payload['related'] = [
                [
                    'relation' => $data['relacion_tipo'],
                    'receipts' => $uuids,
                ],
            ];
        }
    }

    if ($request->boolean('usar_complemento_construccion')) {
        $cc = $request->input('complemento_construccion', []);
        $escapeXml = fn ($value) => htmlspecialchars($value ?? '.', ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $payload['complements'] = [
            [
                'type' => 'custom',
                'data' =>
                    '<servicioparcial:parcialesconstruccion Version="1.0" NumPerLicoAut="'.$escapeXml($cc['num_per_lico_aut'] ?? '').'">' .
                        '<servicioparcial:Inmueble ' .
                            'Calle="'.$escapeXml(($cc['calle'] ?? null) ?: '.').'" ' .
                            'NoExterior="'.$escapeXml(($cc['no_exterior'] ?? null) ?: '.').'" ' .
                            'NoInterior="'.$escapeXml(($cc['no_interior'] ?? null) ?: '.').'" ' .
                            'Colonia="'.$escapeXml(($cc['colonia'] ?? null) ?: '.').'" ' .
                            'Localidad="'.$escapeXml(($cc['localidad'] ?? null) ?: '.').'" ' .
                            'Referencia="'.$escapeXml(($cc['referencia'] ?? null) ?: '.').'" ' .
                            'Municipio="'.$escapeXml(($cc['municipio'] ?? null) ?: '.').'" ' .
                            'Estado="'.$escapeXml(($cc['estado'] ?? null) ?: '.').'" ' .
                            'CodigoPostal="'.$escapeXml(($cc['codigo_postal'] ?? null) ?: '.').'" />' .
                    '</servicioparcial:parcialesconstruccion>',
            ],
        ];

        $payload['pdf_custom_section'] = '
            <div>
                <strong>Complemento Servicios Parciales de Construccion</strong>
                <table style="width:100%; margin-top:8px; font-size:11px;">
                    <tr><td><strong>Permiso / Licencia / Autorizacion:</strong></td><td>' . htmlspecialchars($cc['num_per_lico_aut'] ?? '', ENT_QUOTES) . '</td></tr>
                    <tr><td><strong>Calle:</strong></td><td>' . htmlspecialchars($cc['calle'] ?? '', ENT_QUOTES) . '</td></tr>
                    <tr><td><strong>C.P.:</strong></td><td>' . htmlspecialchars($cc['codigo_postal'] ?? '', ENT_QUOTES) . '</td></tr>
                    <tr><td><strong>Municipio:</strong></td><td>' . htmlspecialchars($cc['municipio'] ?? '', ENT_QUOTES) . '</td></tr>
                    <tr><td><strong>Estado:</strong></td><td>' . htmlspecialchars($cc['estado'] ?? '', ENT_QUOTES) . '</td></tr>
                </table>
            </div>
        ';
    }

    return $payload;
}

    /**
     * Generar CFDI.
     */
   public function store(Request $request, FacturapiService $facturapiService)
{
        // dd($request->all());
//         dd(
//     $request->input('usar_complemento_construccion'),
//     $request->input('complemento_construccion')
// );
            $data = $request->validate([
                'sat_empresa_id' => ['required', 'exists:sat_empresas,id'],
                'cliente_id' => ['required', 'exists:clientes,id'],
                'obra_id' => ['nullable', 'exists:obras,id'],
                'obra_factura_borrador_id' => ['nullable', 'exists:obra_factura_borradores,id'],
                'cfdi_borrador_id' => ['nullable', 'exists:sat_factura_borradores,id'],

                'uso_cfdi' => ['required', 'string', 'max:10'],
                'metodo_pago' => ['required', 'string', 'max:10'],
                'forma_pago' => ['nullable', 'string', 'max:10'],
                'amortizacion' => ['nullable', 'numeric', 'min:0'],
                'descuento' => ['nullable', 'numeric', 'min:0'],
                'retenciones' => ['nullable', 'numeric', 'min:0'],

                'conceptos' => ['required', 'array', 'min:1'],
                'conceptos.*.sat_concepto_id' => ['nullable', 'exists:sat_conceptos,id'],
                'conceptos.*.descripcion' => ['required', 'string', 'max:255'],
                'conceptos.*.clave_producto_servicio' => ['required', 'string', 'max:20'],
                'conceptos.*.clave_unidad' => ['required', 'string', 'max:20'],
                'conceptos.*.unidad' => ['nullable', 'string', 'max:100'],
                'conceptos.*.cantidad' => ['required', 'numeric', 'min:0.000001'],
                'conceptos.*.precio_unitario' => ['required', 'numeric', 'min:0'],
                'conceptos.*.iva_tasa' => ['required', 'numeric', 'min:0'],
                'tipo_iva' => ['required', 'in:0.16,0.08,0,exento,sin_iva'],

                'conceptos.*.incluye_iva' => ['nullable'],

                'usar_relacion' => ['nullable'],
                'relacion_tipo' => ['required_if:usar_relacion,1', 'nullable', 'string', 'max:2'],
                'relacion_uuids' => ['required_if:usar_relacion,1', 'nullable', 'string'],

                'usar_complemento_construccion' => ['nullable'],
                'complemento_construccion' => ['nullable', 'array'],
                'complemento_construccion.num_per_lico_aut' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:50'],
                // 'complemento_construccion.calle' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:255'],
             

                'complemento_construccion.calle' => [
                    Rule::requiredIf(fn() => 
                        $request->input('usar_complemento_construccion') == 1 
                        && !is_null($request->input('complemento_construccion.calle'))
                    ),
                    'nullable', 
                    'string', 
                    'max:255'
                ],
                'complemento_construccion.no_exterior' => ['nullable', 'string', 'max:50'],
                'complemento_construccion.no_interior' => ['nullable', 'string', 'max:50'],
                'complemento_construccion.colonia' => ['nullable', 'string', 'max:100'],
                'complemento_construccion.localidad' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:100'],
                'complemento_construccion.referencia' => ['nullable', 'string', 'max:255'],
                'complemento_construccion.municipio' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:100'],
                'complemento_construccion.estado' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:2'],
                'complemento_construccion.codigo_postal' => ['required_if:usar_complemento_construccion,1', 'nullable', 'string', 'max:5'],
            ]);

    $borrador = null;

    if (!empty($data['obra_factura_borrador_id'])) {
        abort_unless(auth()->user()?->can('obra_factura_borradores.invoice.access'), 403);

        $borrador = ObraFacturaBorrador::findOrFail($data['obra_factura_borrador_id']);

        if (
            $borrador->estatus !== ObraFacturaBorrador::ESTATUS_AUTORIZADO
            || $borrador->sat_factura_id
        ) {
            return back()
                ->withInput()
                ->with('error', 'Solo se pueden timbrar borradores autorizados y sin factura ligada.');
        }

        if ((int) $borrador->cliente_id !== (int) $data['cliente_id']) {
            return back()
                ->withInput()
                ->with('error', 'El cliente no coincide con el borrador autorizado.');
        }

        if ((int) $borrador->obra_id !== (int) ($data['obra_id'] ?? 0)) {
            return back()
                ->withInput()
                ->with('error', 'La obra no coincide con el borrador autorizado.');
        }
    }

    $empresa = SatEmpresa::findOrFail($data['sat_empresa_id']);
    $cliente = Cliente::findOrFail($data['cliente_id']);

    $facturapi = $facturapiService->client();
    $regimenFiscal = $cliente->regimen_fiscal;

        if (!$regimenFiscal) {
            throw new \RuntimeException('El cliente no tiene régimen fiscal configurado.');
        }

        if (!$cliente->codigo_postal) {
            throw new \RuntimeException('El cliente no tiene código postal fiscal configurado.');
        }

    try {

        /*
        |--------------------------------------------------------------------------
        | 1. Sincronizar cliente con Facturapi si no existe
        |--------------------------------------------------------------------------
        */
        // \Log::info('ENTRO STORE FACTURACION');
        if (!$cliente->facturapi_customer_id) {
// \Log::info('ANTES CUSTOMER');
            $customer = $facturapi->Customers->create([
                'legal_name' => $cliente->razon_social ?: $cliente->nombre_comercial,
                'tax_id' => strtoupper($cliente->rfc),
                'tax_system' => $regimenFiscal,
                'email' => $cliente->email ?: 'facturacion@example.com',
                'address' => [
                    'zip' => $cliente->codigo_postal ?: '44600',
                ],
            ]);

            $cliente->facturapi_customer_id = $customer->id;
            $cliente->save();
            \Log::info('CUSTOMER OK');
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Armar conceptos para Facturapi
        |--------------------------------------------------------------------------
        */
        $items = [];

        $subtotal = 0;
        $iva = 0;
        $descuentoGlobal = round((float) ($data['descuento'] ?? 0), 2);
        $amortizacion = round((float) ($data['amortizacion'] ?? 0), 2);
        $retenciones = round((float) ($data['retenciones'] ?? 0), 2);
        $descuentoFacturapi = round($descuentoGlobal + $amortizacion, 2);
        $subtotalBase = collect($data['conceptos'])->sum(function ($concepto) {
            return (float) $concepto['cantidad'] * (float) $concepto['precio_unitario'];
        });
        $descuentoAplicado = 0.0;
        $conceptosCount = count($data['conceptos']);
        $tipoIva = $data['tipo_iva'];
        $ivaTasaNum = match(true) {
                in_array($tipoIva, ['0.16', '0.08']) => (float) $tipoIva,
                $tipoIva === '0'                     => 0.0,
                default                              => 0.0, // exento y sin_iva no aplica tasa
            };


        $total = 0;

        foreach ($data['conceptos'] as $index => $concepto) {

    $cantidad = (float) $concepto['cantidad'];
    $precio   = (float) $concepto['precio_unitario'];

    // Cálculo de montos
    $lineSubtotalBruto = round($cantidad * $precio, 2);
    $lineDiscount = 0.0;

    if ($descuentoFacturapi > 0 && $subtotalBase > 0) {
        $lineDiscount = $index === $conceptosCount - 1
            ? round($descuentoFacturapi - $descuentoAplicado, 2)
            : round($descuentoFacturapi * ($lineSubtotalBruto / $subtotalBase), 2);
        $descuentoAplicado += $lineDiscount;
    }

    $lineSubtotal = max(0, round($lineSubtotalBruto - $lineDiscount, 2));
    $lineIva      = in_array($tipoIva, ['0.16', '0.08'])
                        ? round($lineSubtotal * $ivaTasaNum, 2)
                        : 0.0;
    $lineTotal    = $lineSubtotal + $lineIva;

    $subtotal += $lineSubtotal;
    $iva      += $lineIva;
    $total    += $lineTotal;

    // Producto para FacturAPI
    $product = [
        'description' => $concepto['descripcion'],
        'product_key' => $concepto['clave_producto_servicio'],
        'unit_key'    => $concepto['clave_unidad'] ?: 'H87',
        'unit_name'   => $concepto['unidad'] ?: 'Pieza',
        'price'       => $precio,
        'tax_included'=> false,
        'taxability'  => $tipoIva === 'sin_iva' ? '01' : '02',
    ];

    $product['taxes'] = match(true) {
        $tipoIva === 'exento'                    => [['type' => 'IVA', 'factor' => 'Exento']],
        $tipoIva === '0'                         => [['type' => 'IVA', 'rate' => 0.0, 'factor' => 'Tasa']],
        in_array($tipoIva, ['0.16', '0.08'])     => [['type' => 'IVA', 'rate' => $ivaTasaNum]],
        default                                  => [], // sin_iva
    };

    $items[] = [
        'quantity' => $cantidad,
        'discount' => $lineDiscount,
        'product'  => $product,
    ];
}
        // foreach ($data['conceptos'] as $concepto) {

        //     $cantidad = (float) $concepto['cantidad'];
        //     $precio = (float) $concepto['precio_unitario'];
        //     $ivaTasa = (float) $concepto['iva_tasa'];
        //     $incluyeIva = !empty($concepto['incluye_iva']);

        //     $importeBruto = $cantidad * $precio;

        //     if ($incluyeIva && $ivaTasa > 0) {
        //         $lineSubtotal = round($importeBruto / (1 + $ivaTasa), 2);
        //         $lineIva = round($importeBruto - $lineSubtotal, 2);
        //         $lineTotal = round($importeBruto, 2);
        //     } else {
        //         $lineSubtotal = round($importeBruto, 2);
        //         $lineIva = round($lineSubtotal * $ivaTasa, 2);
        //         $lineTotal = round($lineSubtotal + $lineIva, 2);
        //     }

        //     $subtotal += $lineSubtotal;
        //     $iva += $lineIva;
        //     $total += $lineTotal;

        //     $product = [
        //         'description' => $concepto['descripcion'],
        //         'product_key' => $concepto['clave_producto_servicio'],
        //         'unit_key' => $concepto['clave_unidad'] ?: 'H87',
        //         'unit_name' => $concepto['unidad'] ?: 'Pieza',
        //         'price' => $precio,
        //         'tax_included' => $incluyeIva,
        //         'taxability' => $ivaTasa > 0 ? '02' : '01',
        //     ];

        //     if ($ivaTasa > 0) {
        //         $product['taxes'] = [
        //             [
        //                 'type' => 'IVA',
        //                 'rate' => $ivaTasa,
        //             ],
        //         ];
        //     }

        //     $items[] = [
        //         'quantity' => $cantidad,
        //         'product' => $product,
        //     ];
        // }

        /*
        |--------------------------------------------------------------------------
        | 3. Timbrar CFDI sandbox
        |--------------------------------------------------------------------------
        */
        $usarComplementoConstruccion = $request->boolean('usar_complemento_construccion');

$complementoConstruccionXml = null;
$complementoConstruccionPdf = null;

if ($usarComplementoConstruccion) {
    $cc = $request->input('complemento_construccion');

    $escapeXml = fn ($value) => htmlspecialchars($value ?? '.', ENT_XML1 | ENT_QUOTES, 'UTF-8');

    $complementoConstruccionXml =
    '<servicioparcial:parcialesconstruccion Version="1.0" NumPerLicoAut="'.$escapeXml($cc['num_per_lico_aut'] ?? '').'">' .
        '<servicioparcial:Inmueble ' .
            'Calle="'      .$escapeXml($cc['calle']        ?: '.').'" ' .  // ← cambiado
            'NoExterior="' .$escapeXml($cc['no_exterior']  ?: '.').'" ' .
            'NoInterior="' .$escapeXml($cc['no_interior']  ?: '.').'" ' .
            'Colonia="'    .$escapeXml($cc['colonia']       ?: '.').'" ' .
            'Localidad="'  .$escapeXml($cc['localidad']    ?: '.').'" ' .
            'Referencia="' .$escapeXml($cc['referencia']   ?: '.').'" ' .
            'Municipio="'  .$escapeXml($cc['municipio']    ?: '.').'" ' .
            'Estado="'     .$escapeXml($cc['estado']       ?: '.').'" ' .
            'CodigoPostal="'.$escapeXml($cc['codigo_postal'] ?: '.').'" />' .
    '</servicioparcial:parcialesconstruccion>';

    $complementoConstruccionPdf = [
        'title' => 'Complemento Servicios Parciales de Construcción',
        'details' => [
            [
                'key' => 'Permiso, licencia o autorización',
                'value' => $cc['num_per_lico_aut'] ?? '',
            ],
            [
                'key' => 'Inmueble',
                'value' => trim(($cc['calle'] ?? '') . ', CP ' . ($cc['codigo_postal'] ?? '')),
            ],
            [
                'key' => 'Ubicación',
                'value' => trim(($cc['municipio'] ?? '') . ', Estado ' . ($cc['estado'] ?? '')),
            ],
        ],
    ];
}
       $payload = [
            'customer' => $cliente->facturapi_customer_id,
            'items' => $items,
            'payment_form' => $data['forma_pago'] ?? '03',
            'payment_method' => $data['metodo_pago'],
            'use' => $data['uso_cfdi'],
        ];

        if ($request->boolean('usar_relacion') && !empty($data['relacion_uuids'])) {
            $uuids = array_map('trim', explode(',', $data['relacion_uuids']));
            $payload['related'] = [
                [
                    'relation' => $data['relacion_tipo'],
                    'receipts' => array_values(array_filter($uuids))
                ]
            ];
        }

if ($usarComplementoConstruccion) {
    $payload['complements'] = [
        [
            'type' => 'custom',
            'data' => $complementoConstruccionXml,
        ],
    ];

    // ✅ pdf_custom_section debe ser un string HTML
    $payload['pdf_custom_section'] = '
        <div>
            <strong>Complemento Servicios Parciales de Construcción</strong>
            <table style="width:100%; margin-top:8px; font-size:11px;">
                <tr>
                    <td><strong>Permiso / Licencia / Autorización:</strong></td>
                    <td>' . htmlspecialchars($cc['num_per_lico_aut'] ?? '', ENT_QUOTES) . '</td>
                </tr>
                <tr>
                    <td><strong>Calle:</strong></td>
                    <td>' . htmlspecialchars($cc['calle'] ?? '', ENT_QUOTES) . '</td>
                </tr>
                <tr>
                    <td><strong>C.P.:</strong></td>
                    <td>' . htmlspecialchars($cc['codigo_postal'] ?? '', ENT_QUOTES) . '</td>
                </tr>
                <tr>
                    <td><strong>Municipio:</strong></td>
                    <td>' . htmlspecialchars($cc['municipio'] ?? '', ENT_QUOTES) . '</td>
                </tr>
                <tr>
                    <td><strong>Estado:</strong></td>
                    <td>' . htmlspecialchars($cc['estado'] ?? '', ENT_QUOTES) . '</td>
                </tr>
            </table>
        </div>
    ';
}

\Log::info('PAYLOAD FACTURAPI', $payload);

$invoice = $facturapi->Invoices->create($payload);
        

        /*
        |--------------------------------------------------------------------------
        | 4. Descargar XML/PDF
        |--------------------------------------------------------------------------
        */
        $xml = $facturapi->Invoices->downloadXml($invoice->id);
        $pdf = $facturapi->Invoices->downloadPdf($invoice->id);

        $folder = 'sat/facturas/' . $empresa->rfc . '/' . $invoice->uuid;

        $xmlPath = $folder . '/' . $invoice->uuid . '.xml';
        $pdfPath = $folder . '/' . $invoice->uuid . '.pdf';

        Storage::put($xmlPath, $xml);
        Storage::put($pdfPath, $pdf);

        /*
        |--------------------------------------------------------------------------
        | 5. Guardar en BD
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use (
            $data,
            $empresa,
            $cliente,
            $invoice,
            $xmlPath,
            $pdfPath,
            $subtotal,
            $iva,
            $total,
            $descuentoGlobal,
            $amortizacion,
            $retenciones,
            $descuentoFacturapi,
            $subtotalBase,
            $tipoIva,
            $ivaTasaNum,
            $borrador
        ) {
\Log::info('ANTES INVOICE');
            $totalFactura = max(0, round($total - $retenciones, 2));

            $factura = SatFactura::create([
                'sat_empresa_id' => $empresa->id,
                'cliente_id' => $cliente->id,
                'obra_id' => $data['obra_id'] ?? null,
                'orden_compra_id' => null,

                'facturapi_invoice_id' => $invoice->id,
                'facturapi_customer_id' => $cliente->facturapi_customer_id,

                'uuid' => $invoice->uuid ?? null,
                'serie' => $invoice->series ?? null,
                'folio' => $invoice->folio_number ?? null,
                'tipo_comprobante' => $invoice->type ?? 'I',
                'cfdi_version' => $invoice->cfdi_version ?? '4.0',

                'receptor_rfc' => $cliente->rfc,
                'receptor_nombre' => $cliente->razon_social ?: $cliente->nombre_comercial,
                'receptor_regimen' => $cliente->regimen_fiscal,
                'receptor_cp' => $cliente->codigo_postal,
                'uso_cfdi' => $data['uso_cfdi'],

                'metodo_pago' => $data['metodo_pago'],
                'forma_pago' => $data['forma_pago'] ?? '03',
                'moneda' => $invoice->currency ?? 'MXN',
                'tipo_cambio' => $invoice->exchange ?? 1,

                'subtotal' => round($subtotal, 2),
                'descuento' => $descuentoGlobal,
                'amortizacion' => $amortizacion,
                'iva' => round($iva, 2),
                'retenciones' => $retenciones,
                'total' => $totalFactura,

                'estado' => 'timbrada',
                'fecha_emision' => isset($invoice->date) ? Carbon::parse($invoice->date) : now(),
                'fecha_timbrado' => isset($invoice->stamp->date) ? Carbon::parse($invoice->stamp->date) : now(),

                'xml_path' => $xmlPath,
                'pdf_path' => $pdfPath,

                'facturapi_response' => json_decode(json_encode($invoice), true),
            ]);

            if ($borrador) {
                $borrador->update([
                    'sat_factura_id' => $factura->id,
                    'estatus' => ObraFacturaBorrador::ESTATUS_FACTURADO,
                    'facturado_por' => auth()->id(),
                    'facturado_at' => now(),
                ]);
            }

            $descuentoAplicadoConceptos = 0.0;
            $retencionAplicadaConceptos = 0.0;
            $conceptosCount = count($data['conceptos']);

            foreach ($data['conceptos'] as $index => $concepto) {
                $cantidad = (float) $concepto['cantidad'];
                $precio = (float) $concepto['precio_unitario'];
                $importeBruto = round($cantidad * $precio, 2);
                $lineDiscount = 0.0;
                $lineRetencion = 0.0;

                if ($descuentoFacturapi > 0 && $subtotalBase > 0) {
                    $lineDiscount = $index === $conceptosCount - 1
                        ? round($descuentoFacturapi - $descuentoAplicadoConceptos, 2)
                        : round($descuentoFacturapi * ($importeBruto / $subtotalBase), 2);
                    $descuentoAplicadoConceptos += $lineDiscount;
                }

                $lineSubtotal = max(0, round($importeBruto - $lineDiscount, 2));
                $lineIva = in_array($tipoIva, ['0.16', '0.08'])
                    ? round($lineSubtotal * $ivaTasaNum, 2)
                    : 0.0;

                if ($retenciones > 0 && $subtotal > 0) {
                    $lineRetencion = $index === $conceptosCount - 1
                        ? round($retenciones - $retencionAplicadaConceptos, 2)
                        : round($retenciones * ($lineSubtotal / $subtotal), 2);
                    $retencionAplicadaConceptos += $lineRetencion;
                }

                $lineTotal = max(0, round($lineSubtotal + $lineIva - $lineRetencion, 2));

                SatFacturaConcepto::create([
                    'sat_factura_id' => $factura->id,

                    'descripcion' => $concepto['descripcion'],
                    'cantidad' => $cantidad,
                    'unidad' => $concepto['unidad'] ?? null,

                    'clave_producto_servicio' => $concepto['clave_producto_servicio'],
                    'clave_unidad' => $concepto['clave_unidad'],

                    'precio_unitario' => $precio,
                    'descuento' => $lineDiscount,

                    'subtotal' => $lineSubtotal,
                    'iva' => $lineIva,
                    'retenciones' => $lineRetencion,
                    'total' => $lineTotal,

                    'taxes' => in_array($tipoIva, ['0.16', '0.08', '0'])
                        ? [
                            [
                                'type' => 'IVA',
                                'rate' => $ivaTasaNum,
                            ],
                        ]
                        : null,

                    'facturapi_payload' => $concepto,
                ]);
            }

            if (!empty($data['cfdi_borrador_id'])) {
                SatFacturaBorrador::where('id', $data['cfdi_borrador_id'])
                    ->where(function ($query) {
                        $query->whereNull('user_id')
                            ->orWhere('user_id', auth()->id());
                    })
                    ->update([
                        'estado' => 'timbrado',
                        'sat_factura_id' => $factura->id,
                    ]);
            }
        });
\Log::info('INVOICE OK', [
    'invoice_id' => $invoice->id ?? null,
]);
        return redirect()
            ->route('sat.facturacion.index')
            ->with('success', 'Factura timbrada correctamente en sandbox.');

    } catch (\Throwable $e) {

    \Log::error('ERROR FACTURACION', [
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
    ]);

    return back()
        ->withInput()
        ->with('error', 'Error al timbrar CFDI: ' . $e->getMessage());
}
}

    /**
     * Mostrar detalle de factura.
     */
   public function show(SatFactura $factura)
{
    $factura->load([
        'empresa',
        'cliente',
        'obra',
        'ordenCompra',
        'conceptos',
    ]);

    $zipUrl = URL::temporarySignedRoute(
        'sat.facturacion.zip',
        now()->addMinutes(30),
        ['factura' => $factura->id]
    );

    $folio = trim(($factura->serie ? $factura->serie . '-' : '') . ($factura->folio ?? $factura->id));
    $whatsappMessage = implode("\n", [
        'Factura ' . $folio,
        'Cliente: ' . ($factura->receptor_nombre ?? 'N/A'),
        'Total: MXN ' . number_format((float) $factura->total, 2),
        'Descarga PDF y XML aqui:',
        $zipUrl,
        '',
        'Este enlace expira en 30 minutos.',
    ]);

    $whatsappUrl = 'https://wa.me/?text=' . rawurlencode($whatsappMessage);

    return view('sat.facturacion.show', compact('factura', 'zipUrl', 'whatsappUrl'));
}

public function downloadXml(SatFactura $factura)
{
    if (!$factura->xml_path || !Storage::exists($factura->xml_path)) {
        abort(404, 'XML no encontrado.');
    }

    $filename = ($factura->serie ?? 'F') . '-' . ($factura->folio ?? $factura->id) . '.xml';

    return Storage::download($factura->xml_path, $filename, [
        'Content-Type' => 'application/xml',
    ]);
}

public function downloadPdf(SatFactura $factura)
{
    if (!$factura->pdf_path || !Storage::exists($factura->pdf_path)) {
        abort(404, 'PDF no encontrado.');
    }

    $filename = ($factura->serie ?? 'F') . '-' . ($factura->folio ?? $factura->id) . '.pdf';

    return Storage::download($factura->pdf_path, $filename, [
        'Content-Type' => 'application/pdf',
    ]);
}

public function downloadZip(SatFactura $factura)
{
    if (!$factura->xml_path || !Storage::exists($factura->xml_path)) {
        abort(404, 'XML no encontrado.');
    }

    if (!$factura->pdf_path || !Storage::exists($factura->pdf_path)) {
        abort(404, 'PDF no encontrado.');
    }

    if (!class_exists(ZipArchive::class)) {
        abort(500, 'El servidor no tiene habilitada la extension ZIP.');
    }

    $baseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim(($factura->serie ? $factura->serie . '-' : '') . ($factura->folio ?? $factura->id)));
    $baseName = trim($baseName, '-') ?: 'factura-' . $factura->id;

    $tempDir = storage_path('app/temp');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $zipName = $baseName . '.zip';
    $zipPath = $tempDir . DIRECTORY_SEPARATOR . uniqid($baseName . '-', true) . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        abort(500, 'No se pudo generar el ZIP de la factura.');
    }

    $zip->addFile(Storage::path($factura->xml_path), $baseName . '.xml');
    $zip->addFile(Storage::path($factura->pdf_path), $baseName . '.pdf');
    $zip->close();

    return response()
        ->download($zipPath, $zipName, ['Content-Type' => 'application/zip'])
        ->deleteFileAfterSend(true);
}

public function cancelar(Request $request, SatFactura $factura)
{
    $data = $request->validate([
        'motivo_cancelacion' => ['nullable', 'string', 'in:01,02,03,04'],
        'sustitucion_uuid' => ['nullable', 'string', 'max:80'],
    ]);

    if (!$factura->facturapi_invoice_id) {
        return back()->with('error', 'La factura no tiene ID de Facturapi.');
    }

    if ($factura->estado === 'cancelada') {
        return back()->with('error', 'Esta factura ya está cancelada.');
    }

    if ($factura->estado !== 'timbrada') {
        return back()->with('error', 'Solo se pueden cancelar facturas timbradas.');
    }

    $motivo = $data['motivo_cancelacion'] ?? '02';
    $sustitucion = trim((string) ($data['sustitucion_uuid'] ?? ''));

    if ($motivo === '01' && $sustitucion === '') {
        return back()->with('error', 'El motivo 01 requiere indicar el UUID o ID de la factura sustituta.');
    }

    try {
        $factura->update([
            'estado' => 'cancelacion_solicitada',
            'error_message' => null,
        ]);

        $query = ['motive' => $motivo];

        if ($sustitucion !== '') {
            $query['substitution'] = $sustitucion;
        }

        $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
    ->delete(
        'https://www.facturapi.io/v2/invoices/' .
        $factura->facturapi_invoice_id .
        '?' . http_build_query($query)
    );

        if (!$response->successful()) {
            $error = $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            $factura->update([
                'estado' => 'timbrada',
                'error_message' => $error,
                'facturapi_response' => $response->json(),
            ]);

            return back()->with('error', 'Error al cancelar CFDI: ' . $error);
        }

        $body = $response->json();
        $nuevoEstado = $this->actualizarEstadoLocalDesdeFacturapi($factura, $body);

        return back()->with(
            'success',
            $nuevoEstado === 'cancelada'
                ? 'CFDI cancelado correctamente.'
                : 'Solicitud de cancelación enviada correctamente.'
        );

    } catch (\Throwable $e) {
        $factura->update([
            'estado' => 'timbrada',
            'error_message' => $e->getMessage(),
        ]);

        return back()->with('error', 'Error al cancelar CFDI: ' . $e->getMessage());
    }
}

public function sincronizarCancelacion(SatFactura $factura)
{
    if (!$factura->facturapi_invoice_id) {
        return back()->with('error', 'La factura no tiene ID de Facturapi.');
    }

    try {
        $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->put('https://www.facturapi.io/v2/invoices/' . $factura->facturapi_invoice_id . '/status');

        if (!$response->successful()) {
            $error = $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            return back()->with('error', 'Error al actualizar estatus: ' . $error);
        }

        $nuevoEstado = $this->actualizarEstadoLocalDesdeFacturapi($factura, $response->json());

        return back()->with(
            'success',
            $nuevoEstado === 'cancelada'
                ? 'Estatus actualizado: la factura ya está cancelada.'
                : 'Estatus actualizado desde Facturapi.'
        );
    } catch (\Throwable $e) {
        return back()->with('error', 'Error al actualizar estatus: ' . $e->getMessage());
    }
}

private function actualizarEstadoLocalDesdeFacturapi(SatFactura $factura, array $body): string
{
    $status = $body['status'] ?? null;
    $cancelacion = $body['cancellation_status'] ?? null;

    $nuevoEstado = match (true) {
        $status === 'canceled' => 'cancelada',
        in_array($cancelacion, ['pending', 'verifying'], true) => 'cancelacion_solicitada',
        $factura->estado === 'cancelacion_solicitada' => 'timbrada',
        default => $factura->estado,
    };

    $factura->update([
        'estado' => $nuevoEstado,
        'fecha_cancelacion' => $nuevoEstado === 'cancelada'
            ? ($factura->fecha_cancelacion ?? now())
            : null,
        'facturapi_response' => $body,
        'error_message' => null,
    ]);

    return $nuevoEstado;
}
public function acuseCancelacion(SatFactura $factura, string $format)
{
    if ($factura->estado !== 'cancelada') {
        return back()->with('error', 'La factura no está cancelada.');
    }

    if (!in_array($format, ['pdf', 'xml'])) {
        abort(404);
    }

    if (!$factura->facturapi_invoice_id) {
        return back()->with('error', 'La factura no tiene ID de Facturapi.');
    }

    try {

        $response = Http::withBasicAuth(
            config('services.facturapi.secret_key'),
            ''
        )->get(
            'https://www.facturapi.io/v2/invoices/' .
            $factura->facturapi_invoice_id .
            '/cancellation_receipt/' .
            $format
        );

        if (!$response->successful()) {

            $error = $response->json('message')
                ?? $response->body();

            return back()->with(
                'error',
                'Error al descargar acuse: ' . $error
            );
        }

        $filename = 'acuse-cancelacion-' .
            ($factura->uuid ?? $factura->id) .
            '.' .
            $format;

        return response(
            $response->body(),
            200,
            [
                'Content-Type' =>
                    $format === 'pdf'
                        ? 'application/pdf'
                        : 'application/xml',

                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',
            ]
        );

    } catch (\Throwable $e) {

        return back()->with(
            'error',
            'Error al descargar acuse: ' . $e->getMessage()
        );
    }
}
public function enviar(Request $request, SatFactura $factura)
{
    $data = $request->validate([
        'email_destino' => ['nullable', 'email', 'max:255'],
        'email_adicional' => ['nullable', 'email', 'max:255'],
    ]);

    $email = $data['email_destino']
        ?? $factura->email_destino
        ?? $factura->cliente?->email;

    if (!$email) {
        return back()->with(
            'error',
            'La factura no tiene correo destino.'
        );
    }

    $destinatarios = collect([
        $email,
        $data['email_adicional'] ?? null,
    ])
        ->filter()
        ->unique()
        ->values()
        ->all();

    try {

        Mail::mailer(config('services.facturacion_mail.mailer', config('mail.default')))
            ->to($destinatarios)
            ->send(
            new SatFacturaMail($factura)
        );

        $factura->update([
            'email_enviado_at' => now(),
            'email_destino' => $email,
        ]);

        return back()->with(
            'success',
            'Factura enviada correctamente.'
        );

    } catch (\Throwable $e) {

        return back()->with(
            'error',
            'Error al enviar factura: ' . $e->getMessage()
        );
    }
}
}
