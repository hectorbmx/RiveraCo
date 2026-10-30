<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use App\Mail\SatFacturaMail;
use App\Services\Mail\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SatFacturaPagoController extends Controller
{
    //
    public function show(SatFacturaPago $pago)
    {
        $pago->load([
            'factura.cliente',
            'factura.obra',
            'documentos.factura.cliente',
            'documentos.factura.obra',
            'pagosInternosObra.obra',
            'pagosInternosObra.cuentaBanco',
            'pagosInternosObra.metodoPago',
            'pagosInternosObra.registradoPor',
        ]);

        return view('sat.facturacion.pagos.show', compact('pago'));
    }

    public function store(Request $request, SatFactura $factura)
    {
        $request->merge(['factura_id' => $factura->id,
            'facturas' => [['factura_id' => $factura->id, 'monto' => $request->input('monto')]]]);

        return app(SatComplementoPagoController::class)->store($request, app(\App\Services\Facturacion\SatComplementoPagoService::class));
    }

public function xml(SatFacturaPago $pago)
{
    if ($pago->estado === 'timbrado' && $pago->facturapi_invoice_id && (! $pago->xml_path || !Storage::disk('local')->exists($pago->xml_path))) {
        app(\App\Services\Facturacion\SatComplementoPagoService::class)->descargarArchivos($pago);
    }
    if (!$pago->xml_path || !Storage::disk('local')->exists($pago->xml_path)) {
        return back()->with('error', 'El XML del complemento de pago no existe.');
    }

    return Storage::disk('local')->download(
        $pago->xml_path,
        'pago-' . ($pago->uuid ?? $pago->id) . '.xml',
        [
            'Content-Type' => 'application/xml',
        ]
    );
}

public function pdf(SatFacturaPago $pago)
{
    if ($pago->estado === 'timbrado' && $pago->facturapi_invoice_id && (! $pago->pdf_path || !Storage::disk('local')->exists($pago->pdf_path))) {
        app(\App\Services\Facturacion\SatComplementoPagoService::class)->descargarArchivos($pago);
    }
    if (!$pago->pdf_path || !Storage::disk('local')->exists($pago->pdf_path)) {
        return back()->with('error', 'El PDF del complemento de pago no existe.');
    }

    $filename = 'pago-' . ($pago->uuid ?? $pago->id) . '.pdf';

    if (request()->boolean('download')) {
        return Storage::disk('local')->download($pago->pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    return response(Storage::disk('local')->get($pago->pdf_path), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
}

public function acuseCancelacion(SatFacturaPago $pago, string $format)
{
    if ($pago->estado !== 'cancelado') {
        return back()->with('error', 'El complemento no esta cancelado.');
    }

    if (!in_array($format, ['pdf', 'xml'], true)) {
        abort(404);
    }

    if (!$pago->facturapi_invoice_id) {
        return back()->with('error', 'El complemento no tiene ID de Facturapi.');
    }

    try {
        $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->get(
                'https://www.facturapi.io/v2/invoices/' .
                $pago->facturapi_invoice_id .
                '/cancellation_receipt/' .
                $format
            );

        if (!$response->successful()) {
            $error = $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            return back()->with('error', 'Error al descargar acuse: ' . $error);
        }

        $filename = 'acuse-cancelacion-pago-' . ($pago->uuid ?? $pago->id) . '.' . $format;

        return response($response->body(), 200, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    } catch (\Throwable $e) {
        return back()->with('error', 'Error al descargar acuse: ' . $e->getMessage());
    }
}

public function enviar(Request $request, SatFacturaPago $pago, MicrosoftGraphMailService $graphMail)
{
    $pago->loadMissing('factura.cliente');
    $factura = $pago->factura;

    $data = $request->validate([
        'email_destino' => ['nullable', 'email', 'max:255'],
        'email_adicional' => ['nullable', 'email', 'max:255'],
    ]);

    if (!$factura) {
        return back()->with('error', 'El complemento no tiene factura relacionada.');
    }

    if (!$pago->xml_path && !$pago->pdf_path) {
        return back()->with('error', 'El complemento no tiene XML o PDF para enviar.');
    }

    $email = $data['email_destino']
        ?? $factura->email_destino
        ?? $factura->cliente?->email;

    if (!$email) {
        return back()->with('error', 'La factura no tiene correo destino.');
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
        if (strtolower((string) config('services.facturacion_mail.provider')) === 'graph') {
            $graphMail->sendSatFactura($factura, $destinatarios, $pago);
        } else {
            Mail::mailer(config('services.facturacion_mail.mailer', config('mail.default')))
                ->to($destinatarios)
                ->send(new SatFacturaMail($factura, $pago));
        }

        $factura->update([
            'email_destino' => $email,
        ]);

        return back()->with('success', 'Complemento de pago enviado correctamente.');
    } catch (\Throwable $e) {
        return back()->with('error', 'Error al enviar complemento de pago: ' . $e->getMessage());
    }
}
public function cancelar(Request $request, SatFacturaPago $pago)
{
    $data = $request->validate([
        'motivo_cancelacion' => ['nullable', 'string', 'in:01,02,03,04'],
        'sustitucion_uuid' => ['nullable', 'string', 'max:80'],
    ]);

    if (!$pago->facturapi_invoice_id) {
        return back()->with('error', 'El complemento no tiene ID de Facturapi.');
    }

    if ($pago->estado === 'cancelado') {
        return back()->with('error', 'Este complemento ya esta cancelado.');
    }

    if (!in_array($pago->estado, ['timbrado', 'cancelacion_pendiente'], true)) {
        return back()->with('error', 'Solo se pueden cancelar complementos timbrados.');
    }

    $motivo = $data['motivo_cancelacion'] ?? '02';
    $sustitucion = trim((string) ($data['sustitucion_uuid'] ?? ''));

    if ($motivo === '01' && $sustitucion === '') {
        return back()->with('error', 'El motivo 01 requiere indicar el UUID del CFDI sustituto.');
    }

    try {
        $query = ['motive' => $motivo];

        if ($sustitucion !== '') {
            $query['substitution'] = $sustitucion;
        }

        // Consultar primero permite recuperar una cancelación fiscal aceptada
        // cuya respuesta previa no alcanzó a guardarse en la base local.
        $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->get('https://www.facturapi.io/v2/invoices/'.$pago->facturapi_invoice_id);
        $remoto = $response->json();
        if ($response->successful() && $pago->estado !== 'cancelacion_pendiente'
            && ! in_array($remoto['status'] ?? '', ['canceled', 'cancelled'], true)
            && ! in_array($remoto['cancellation_status'] ?? '', ['accepted', 'pending', 'verifying'], true)) {
            $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')->delete(
                'https://www.facturapi.io/v2/invoices/' .
                $pago->facturapi_invoice_id .
                '?' . http_build_query($query)
            );
        }

        if (!$response->successful()) {
            $error = $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            $pago->update([
                'error_message' => $error,
                'facturapi_response' => $response->json(),
            ]);

            return back()->with('error', 'Error al cancelar complemento: ' . $error);
        }

        $body = $response->json();
        $aceptada = in_array($body['status'] ?? '', ['canceled', 'cancelled'], true) || ($body['cancellation_status'] ?? '') === 'accepted';
        $rechazada = in_array($body['cancellation_status'] ?? '', ['rejected', 'expired'], true);
        \Illuminate\Support\Facades\DB::transaction(function () use ($pago, $body, $aceptada, $rechazada, $motivo, $sustitucion) {
        $pago->update([
            'estado' => $aceptada ? 'cancelado' : ($rechazada ? 'timbrado' : 'cancelacion_pendiente'),
            'fecha_cancelacion' => $aceptada ? now() : null,
            'cancelado_por' => auth()->id(),
            'motivo_cancelacion' => $motivo,
            'sustitucion_uuid' => $sustitucion !== '' ? $sustitucion : null,
            'facturapi_response' => $body,
            'error_message' => $aceptada ? null : ($rechazada ? 'La cancelación fue rechazada o expiró; el complemento sigue vigente.' : 'Cancelación pendiente de confirmación. Los saldos siguen aplicados a todas las facturas.'),
        ]);

        if ($aceptada) {
        $pago->pagosInternosObra()->update([
            'sat_factura_pago_id' => null,
        ]);
        }
        });

        return redirect()
            ->route('sat.facturacion.pagos.show', $pago)
            ->with($aceptada ? 'success' : 'warning', $aceptada ? 'Complemento completo cancelado correctamente.' : 'Consulta el estado de cancelación antes de generar otro complemento.');
    } catch (\Throwable $e) {
        $pago->update([
            'error_message' => $e->getMessage(),
        ]);

        return back()->with('error', 'Error al cancelar complemento: ' . $e->getMessage());
    }
}
}
