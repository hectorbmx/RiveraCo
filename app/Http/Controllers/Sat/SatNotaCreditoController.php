<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatFactura;
use App\Models\SatFacturaBorrador;
use App\Models\SatEmpresa;
use App\Models\Cliente;
use App\Services\Facturacion\FacturapiService;
use App\Services\Facturacion\NotaCreditoPreviewService;
use App\Services\Facturacion\NotaCreditoTimbradoService;
use App\Services\Mail\MicrosoftGraphMailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SatNotaCreditoController extends Controller
{
    public function show(SatFactura $factura): View
    {
        abort_unless($factura->tipo_comprobante === 'E', 404);
        $factura->load(['empresa', 'cliente', 'obra', 'conceptos', 'timbradoPor']);
        $relaciones = DB::table('sat_factura_relaciones')->where('sat_factura_id', $factura->id)->get();
        $zipUrl = URL::temporarySignedRoute('sat.notas-credito.zip', now()->addMinutes(30), ['factura' => $factura->id]);
        $folio = trim(($factura->serie ? $factura->serie.'-' : '').($factura->folio ?: $factura->id));
        $mensaje = implode("\n", ['Nota de crédito '.$folio, 'Cliente: '.($factura->receptor_nombre ?: 'N/A'), 'Total: '.$factura->moneda.' '.number_format((float) $factura->total, 2), 'Descarga PDF y XML aquí:', $zipUrl, '', 'Este enlace expira en 30 minutos.']);
        $whatsappUrl = 'https://wa.me/?text='.rawurlencode($mensaje);
        return view('sat.notas-credito.show', compact('factura', 'relaciones', 'whatsappUrl'));
    }

    public function enviar(Request $request, SatFactura $factura, MicrosoftGraphMailService $graphMail, FacturapiService $facturapi)
    {
        abort_unless($factura->tipo_comprobante === 'E' && $factura->estado === 'timbrada', 404);
        try {
            $this->asegurarArchivos($factura, $facturapi);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudieron obtener ambos archivos de la nota para enviarlos.');
        }
        return app(SatFacturacionController::class)->enviar($request, $factura, $graphMail);
    }

    public function descargarZip(SatFactura $factura, FacturapiService $facturapi)
    {
        abort_unless($factura->tipo_comprobante === 'E' && $factura->estado === 'timbrada', 404);
        $this->asegurarArchivos($factura, $facturapi);
        return app(SatFacturacionController::class)->downloadZip($factura);
    }

    private function asegurarArchivos(SatFactura $factura, FacturapiService $facturapi): void
    {
        foreach (['xml', 'pdf'] as $formato) {
            $campo = $formato.'_path';
            if ($factura->$campo && Storage::exists($factura->$campo)) continue;
            if (! $factura->facturapi_invoice_id || ! $factura->uuid) throw new \RuntimeException('Nota sin identificador de Facturapi.');
            $contenido = $formato === 'xml' ? $facturapi->client()->Invoices->downloadXml($factura->facturapi_invoice_id) : $facturapi->client()->Invoices->downloadPdf($factura->facturapi_invoice_id);
            $ruta = 'sat/facturas/'.$factura->empresa->rfc.'/'.$factura->uuid.'/'.$factura->uuid.'.'.$formato;
            if (! $contenido || ! Storage::put($ruta, $contenido)) throw new \RuntimeException('No se pudo almacenar el archivo.');
            $factura->update([$campo => $ruta]);
        }
    }

    public function descargarArchivo(SatFactura $factura, string $formato, FacturapiService $facturapi)
    {
        abort_unless($factura->tipo_comprobante === 'E' && in_array($formato, ['xml', 'pdf'], true), 404);
        $ruta = $formato === 'xml' ? $factura->xml_path : $factura->pdf_path;
        if ($ruta && Storage::exists($ruta)) return Storage::download($ruta);
        abort_unless($factura->facturapi_invoice_id, 404);
        try {
            $contenido = $formato === 'xml'
                ? $facturapi->client()->Invoices->downloadXml($factura->facturapi_invoice_id)
                : $facturapi->client()->Invoices->downloadPdf($factura->facturapi_invoice_id);
            return response($contenido, 200, [
                'Content-Type' => $formato === 'xml' ? 'application/xml' : 'application/pdf',
                'Content-Disposition' => 'attachment; filename="nota-credito-'.$factura->id.'.'.$formato.'"',
            ]);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('sat.notas-credito.index')->with('error', 'No fue posible descargar el archivo desde Facturapi.');
        }
    }

    public function timbrar(Request $request, SatFacturaBorrador $borrador, NotaCreditoTimbradoService $servicio)
    {
        abort_unless($request->user()?->can('sat.cfdi_autoriza.access'), 403);
        $this->verificarBorrador($request, $borrador);
        try {
            $estado = $servicio->emitir($borrador, $request->user()->id);
            return $this->respuestaEmision($estado);
        } catch (ValidationException $e) {
            return redirect()->route('sat.notas-credito.index')->with('error', collect($e->errors())->flatten()->first());
        }
    }

    public function consultarEmision(Request $request, SatFacturaBorrador $borrador, NotaCreditoTimbradoService $servicio)
    {
        abort_unless($request->user()?->can('sat.cfdi_autoriza.access'), 403);
        $this->verificarPropietario($request, $borrador);
        try {
            return $this->respuestaEmision($servicio->consultar($borrador));
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('sat.notas-credito.index')->with('error', 'No fue posible consultar la emisión. La solicitud permanece bloqueada; intenta consultar nuevamente.');
        }
    }

    private function respuestaEmision(string $estado)
    {
        $mensajes = [
            'timbrada' => ['success', 'Nota de crédito timbrada correctamente.'],
            'pendiente_facturapi' => ['success', 'Facturapi aceptó la solicitud y continúa procesándola. Consulta su estado antes de intentar otra emisión.'],
            'rechazada' => ['error', 'Facturapi rechazó la solicitud. Revisa los datos del borrador antes de intentar nuevamente.'],
        ];
        [$tipo, $mensaje] = $mensajes[$estado] ?? ['error', 'El resultado de la emisión requiere revisión. No vuelvas a timbrar hasta confirmar el resultado en Facturapi.'];
        return redirect()->route('sat.notas-credito.index')->with($tipo, $mensaje);
    }

    public function previsualizar(Request $request, SatFacturaBorrador $borrador, NotaCreditoPreviewService $preview)
    {
        $this->verificarBorrador($request, $borrador);
        try {
            $payload = $preview->payload($borrador);
            $facturapi = app(FacturapiService::class);
            $preview->verificarEmisor($borrador, $facturapi);
            $pdf = $facturapi->client()->Invoices->previewPdf($payload);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="nota-credito-borrador.pdf"']);
        } catch (ValidationException $exception) {
            return redirect()->route('sat.notas-credito.index')->with('error', collect($exception->errors())->flatten()->first());
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->route('sat.notas-credito.index')->with('error', 'No fue posible obtener la previsualización de Facturapi. Revisa la configuración y vuelve a intentarlo.');
        }
    }

    public function guardarBorrador(Request $request)
    {
        return $this->persistirBorrador($request);
    }

    public function actualizarBorrador(Request $request, SatFacturaBorrador $borrador)
    {
        $this->verificarBorrador($request, $borrador);
        return $this->persistirBorrador($request, $borrador);
    }

    public function eliminarBorrador(Request $request, SatFacturaBorrador $borrador)
    {
        DB::transaction(function () use ($request, $borrador) {
            $actual = SatFacturaBorrador::whereKey($borrador->id)->lockForUpdate()->firstOrFail();
            $this->verificarBorrador($request, $actual);
            $actual->delete();
        });

        return redirect()->route('sat.notas-credito.index')->with('success', 'Borrador de nota de crédito eliminado.');
    }

    private function verificarBorrador(Request $request, SatFacturaBorrador $borrador): void
    {
        abort_unless($borrador->tipo_comprobante === 'E' && $borrador->estado === 'borrador' && ! $borrador->sat_factura_id, 404);
        $this->verificarPropietario($request, $borrador);
    }

    private function verificarPropietario(Request $request, SatFacturaBorrador $borrador): void
    {
        abort_unless($request->user()?->hasRole('super-admin') || $borrador->user_id === null || (int) $borrador->user_id === (int) $request->user()?->id, 403);
    }

    private function persistirBorrador(Request $request, ?SatFacturaBorrador $borrador = null)
    {
        $data = $request->validate([
            'sat_empresa_id' => ['required', Rule::exists('sat_empresas', 'id')->where('activo', true)],
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('activo', true)],
            'motivo' => ['required', Rule::in(['anticipo', 'descuento', 'devolucion'])],
            'tipo_relacion' => ['required', Rule::in(['01', '03', '07'])],
            'uuid_relacionado' => ['required', 'uuid'],
            'subtotal' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99', 'decimal:0,2'],
            'tipo_iva' => ['required', Rule::in(['0.16', 'exento', 'sin_iva'])],
            'moneda' => ['required', 'string', 'size:3'],
        ]);

        $tiposPermitidos = ['anticipo' => '07', 'descuento' => '01', 'devolucion' => '03'];
        if ($data['tipo_relacion'] !== $tiposPermitidos[$data['motivo']]) {
            throw ValidationException::withMessages(['tipo_relacion' => 'El tipo de relación no corresponde al motivo seleccionado.']);
        }

        $factura = SatFactura::query()
            ->where('sat_empresa_id', $data['sat_empresa_id'])
            ->where('cliente_id', $data['cliente_id'])
            ->where('tipo_comprobante', 'I')
            ->where('estado', 'timbrada')
            ->where('uuid', $data['uuid_relacionado'])
            ->first();
        if (! $factura) {
            throw ValidationException::withMessages(['uuid_relacionado' => 'Selecciona una factura de ingreso timbrada del cliente y la empresa elegidos.']);
        }
        if (strtoupper($data['moneda']) !== strtoupper($factura->moneda)) {
            throw ValidationException::withMessages(['moneda' => 'La moneda debe coincidir con la factura seleccionada.']);
        }

        $subtotalCentavos = (int) round((float) $data['subtotal'] * 100);
        $ivaCentavos = $data['tipo_iva'] === '0.16' ? intdiv($subtotalCentavos * 16 + 50, 100) : 0;

        $atributos = [
            'sat_empresa_id' => $factura->sat_empresa_id,
            'cliente_id' => $factura->cliente_id,
            'obra_id' => $factura->obra_id,
            'tipo_comprobante' => 'E',
            'titulo' => 'Nota de crédito',
            'estado' => 'borrador',
            'payload' => [
                'motivo' => $data['motivo'],
                'tipo_relacion' => $data['tipo_relacion'],
                'uuid_relacionado' => $factura->uuid,
                'factura_relacionada_id' => $factura->id,
                'subtotal' => $data['subtotal'],
                'tipo_iva' => $data['tipo_iva'],
                'iva' => number_format($ivaCentavos / 100, 2, '.', ''),
                'total' => number_format(($subtotalCentavos + $ivaCentavos) / 100, 2, '.', ''),
                'moneda' => strtoupper($data['moneda']),
            ],
        ];

        DB::transaction(function () use ($request, $borrador, $atributos) {
            if ($borrador) {
                $actual = SatFacturaBorrador::whereKey($borrador->id)->lockForUpdate()->firstOrFail();
                $this->verificarBorrador($request, $actual);
                $actual->update($atributos);
            } else {
                SatFacturaBorrador::create($atributos + ['user_id' => $request->user()->id]);
            }
        });

        return redirect()->route('sat.notas-credito.index')->with('success', $borrador ? 'Borrador de nota de crédito actualizado.' : 'Borrador de nota de crédito guardado correctamente.');
    }

    public function facturas(Request $request)
    {
        $data = $request->validate([
            'sat_empresa_id' => ['required', 'exists:sat_empresas,id'],
            'cliente_id' => ['required', 'exists:clientes,id'],
        ]);

        $facturas = SatFactura::query()
            ->where('sat_empresa_id', $data['sat_empresa_id'])
            ->where('cliente_id', $data['cliente_id'])
            ->where('tipo_comprobante', 'I')
            ->where('estado', 'timbrada')
            ->whereNotNull('uuid')
            ->where('uuid', '!=', '')
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->get(['id', 'uuid', 'serie', 'folio', 'fecha_emision', 'total', 'moneda'])
            ->map(fn (SatFactura $factura) => [
                'uuid' => $factura->uuid,
                'serie' => $factura->serie,
                'folio' => $factura->folio,
                'fecha_formateada' => $factura->fecha_emision?->format('d/m/Y'),
                'source_label' => 'SIRICO',
                'total' => (float) $factura->total,
                'moneda' => $factura->moneda,
            ]);

        return response()->json(['data' => $facturas]);
    }

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));
        $estado = (string) $request->query('estado', '');
        $estados = [
            'timbrada' => 'Timbrada',
            'borrador' => 'Borrador',
            'cancelacion_solicitada' => 'Cancelación solicitada',
            'cancelada' => 'Cancelada',
            'error' => 'Error',
            'enviando' => 'Enviando',
            'pendiente_facturapi' => 'Pendiente en Facturapi',
            'revision_emision' => 'Emisión por revisar',
        ];

        $consultaNotas = SatFactura::query()
            ->where('tipo_comprobante', 'E')
            ->when(isset($estados[$estado]), fn ($query) => $query->where('estado', $estado))
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query->where('folio', 'like', "%{$busqueda}%")
                        ->orWhere('uuid', 'like', "%{$busqueda}%")
                        ->orWhere('receptor_nombre', 'like', "%{$busqueda}%")
                        ->orWhere('receptor_rfc', 'like', "%{$busqueda}%")
                        ->orWhereHas('cliente', fn ($cliente) => $cliente
                            ->where('razon_social', 'like', "%{$busqueda}%")
                            ->orWhere('nombre_comercial', 'like', "%{$busqueda}%")
                            ->orWhere('rfc', 'like', "%{$busqueda}%"))
                        ->orWhereHas('obra', fn ($obra) => $obra
                            ->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('clave_obra', 'like', "%{$busqueda}%"));
                });
            });

        $consultaBorradores = SatFacturaBorrador::query()
            ->where('tipo_comprobante', 'E')->whereIn('estado', ['borrador', 'enviando', 'pendiente_facturapi', 'revision_emision'])->whereNull('sat_factura_id')
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query->where('payload', 'like', "%{$busqueda}%")
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->where('razon_social', 'like', "%{$busqueda}%")
                            ->orWhere('nombre_comercial', 'like', "%{$busqueda}%")->orWhere('rfc', 'like', "%{$busqueda}%"));
                });
            });

        $consultaNotas->select('id', 'created_at')->selectRaw("'nota' as origen");
        $consultaBorradores->select('id', 'created_at')->selectRaw("'borrador' as origen");
        $notas = $consultaNotas->toBase()->unionAll($consultaBorradores->toBase())
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();
        $filas = collect($notas->items());
        $notasPorId = SatFactura::with(['cliente', 'obra'])->whereIn('id', $filas->where('origen', 'nota')->pluck('id'))->get()->keyBy('id');
        $borradoresPorId = SatFacturaBorrador::with(['cliente', 'obra'])->whereIn('id', $filas->where('origen', 'borrador')->pluck('id'))->get()->keyBy('id');
        $notas->setCollection($filas->map(fn ($fila) => $fila->origen === 'nota' ? $notasPorId[$fila->id] : $borradoresPorId[$fila->id])->filter());
        $relaciones = DB::table('sat_factura_relaciones')
            ->whereIn('sat_factura_id', $notasPorId->keys())
            ->orderBy('id')->get(['sat_factura_id', 'tipo_relacion', 'uuid_relacionado'])->groupBy('sat_factura_id');

        $empresas = SatEmpresa::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'rfc']);
        $clientes = Cliente::where('activo', true)->orderBy('razon_social')->orderBy('nombre_comercial')
            ->get(['id', 'razon_social', 'nombre_comercial', 'rfc']);

        return view('sat.notas-credito.index', compact('notas', 'relaciones', 'busqueda', 'estado', 'estados', 'empresas', 'clientes'));
    }
}
