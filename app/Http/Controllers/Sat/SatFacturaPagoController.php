<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SatFacturaPagoController extends Controller
{
    //
    public function store(Request $request, SatFactura $factura)
{
    $data = $request->validate([
        'fecha_pago' => ['required', 'date'],
        'forma_pago' => ['required', 'string', 'max:5'],
        'monto' => ['required', 'numeric', 'min:0.01'],
    ]);

    if ($factura->estado !== 'timbrada') {
        return back()->with('error', 'Solo se pueden registrar pagos a facturas timbradas.');
    }

    if ($factura->metodo_pago !== 'PPD') {
        return back()->with('error', 'Solo las facturas PPD pueden generar complemento de pago.');
    }

    if (!$factura->facturapi_invoice_id || !$factura->uuid) {
        return back()->with('error', 'La factura no tiene UUID o ID de Facturapi.');
    }

    $totalPagado = $factura->pagos()
        ->whereIn('estado', ['timbrado', 'registrado'])
        ->sum('monto');

    $saldoAnterior = round($factura->total - $totalPagado, 2);
    $monto = round((float) $data['monto'], 2);
    $saldoInsoluto = round($saldoAnterior - $monto, 2);

    if ($monto > $saldoAnterior) {
        return back()->with('error', 'El monto pagado no puede ser mayor al saldo pendiente.');
    }

    $numeroParcialidad = $factura->pagos()
        ->whereIn('estado', ['timbrado', 'registrado'])
        ->count() + 1;

    try {
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
                                "exchange" => 1.0,
                                'date' => \Carbon\Carbon::parse($data['fecha_pago'])->toIso8601String(),

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
// dd($payload);
        $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->post('https://www.facturapi.io/v2/invoices', $payload);

        if (!$response->successful()) {
            $error = $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            return back()->with('error', 'Error al generar complemento de pago: ' . $error);
        }

        $body = $response->json();
// dd($body);
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
        

        if (!empty($body['id'])) {
            $this->guardarArchivosPago($pago);
        }

        return back()->with('success', 'Complemento de pago generado correctamente.');

    } catch (\Throwable $e) {
        return back()->with('error', 'Error al generar complemento de pago: ' . $e->getMessage());
    }
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
public function xml(SatFacturaPago $pago)
{
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
    if (!$pago->pdf_path || !Storage::disk('local')->exists($pago->pdf_path)) {
        return back()->with('error', 'El PDF del complemento de pago no existe.');
    }

    return Storage::disk('local')->download(
        $pago->pdf_path,
        'pago-' . ($pago->uuid ?? $pago->id) . '.pdf',
        [
            'Content-Type' => 'application/pdf',
        ]
    );
}

}
