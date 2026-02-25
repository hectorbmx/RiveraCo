<?php
namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContpaqiFacturaImportController extends Controller
{
  public function store(Request $request)
  {
    $data = $request->validate([
      'invoices' => 'required|array|min:1',
      'invoices.*.uuid' => 'required|string',
      'invoices.*.doc_sat_id' => 'nullable|integer',
      'invoices.*.serie' => 'nullable|string|max:20',
      'invoices.*.folio' => 'nullable|string|max:30',
      'invoices.*.tipo' => 'nullable|string|max:10',
      'invoices.*.rfc_emisor' => 'nullable|string|max:20',
      'invoices.*.rfc_receptor' => 'nullable|string|max:20',
      'invoices.*.razon_social' => 'nullable|string|max:255',
      'invoices.*.fecha_emision' => 'nullable',
      'invoices.*.fecha_timbrado' => 'nullable',
      'invoices.*.moneda' => 'nullable|string|max:10',
      'invoices.*.tipo_cambio' => 'nullable|numeric',
      'invoices.*.forma_pago' => 'nullable|string|max:10',
      'invoices.*.metodo_pago' => 'nullable|string|max:10',
      'invoices.*.uso_cfdi' => 'nullable|string|max:10',
      'invoices.*.subtotal' => 'nullable|numeric',
      'invoices.*.descuento' => 'nullable|numeric',
      'invoices.*.iva_0' => 'nullable|numeric',
      'invoices.*.iva_8' => 'nullable|numeric',
      'invoices.*.iva_16' => 'nullable|numeric',
      'invoices.*.ieps' => 'nullable|numeric',
      'invoices.*.iva_retenido' => 'nullable|numeric',
      'invoices.*.isr_retenido' => 'nullable|numeric',
      'invoices.*.total' => 'nullable|numeric',
      'invoices.*.status' => 'nullable|string|max:50',
      'invoices.*.fecha_cancelacion' => 'nullable',
      'invoices.*.xml' => 'nullable|string',
    ]);

    $imported = 0;
    $updated  = 0;

    DB::transaction(function () use ($data, &$imported, &$updated) {
      foreach ($data['invoices'] as $inv) {

        // Si quieres reforzar: solo Vigente
        if (($inv['status'] ?? null) !== 'Vigente') {
          continue;
        }

        $values = [
          'source_system' => 'CONTPAQi-ComercialStart',
          'doc_sat_id' => $inv['doc_sat_id'] ?? null,
          'uuid' => $inv['uuid'],
          'serie' => $inv['serie'] ?? null,
          'folio' => $inv['folio'] ?? null,
          'tipo_comprobante' => $inv['tipo'] ?? null,
          'rfc_emisor' => $inv['rfc_emisor'] ?? null,
          'rfc_receptor' => $inv['rfc_receptor'] ?? null,
          'razon_social' => $inv['razon_social'] ?? null,
          'fecha_emision' => $inv['fecha_emision'] ?? null,
          'fecha_timbrado' => $inv['fecha_timbrado'] ?? null,
          'moneda' => $inv['moneda'] ?? null,
          'tipo_cambio' => $inv['tipo_cambio'] ?? null,
          'forma_pago' => $inv['forma_pago'] ?? null,
          'metodo_pago' => $inv['metodo_pago'] ?? null,
          'uso_cfdi' => $inv['uso_cfdi'] ?? null,
          'subtotal' => $inv['subtotal'] ?? null,
          'descuento' => $inv['descuento'] ?? null,
          'iva_0' => $inv['iva_0'] ?? null,
          'iva_8' => $inv['iva_8'] ?? null,
          'iva_16' => $inv['iva_16'] ?? null,
          'ieps' => $inv['ieps'] ?? null,
          'iva_retenido' => $inv['iva_retenido'] ?? null,
          'isr_retenido' => $inv['isr_retenido'] ?? null,
          'total' => $inv['total'] ?? null,
          'status' => $inv['status'] ?? null,
          'fecha_cancelacion' => $inv['fecha_cancelacion'] ?? null,
          'xml' => $inv['xml'] ?? null,
        ];

        $factura = Factura::where('source_system', 'CONTPAQi-ComercialStart')
          ->where('uuid', $inv['uuid'])
          ->first();

        if ($factura) {
          $factura->fill($values)->save();
          $updated++;
        } else {
          Factura::create($values);
          $imported++;
        }
      }
    });

    return response()->json([
      'ok' => true,
      'imported' => $imported,
      'updated' => $updated,
    ]);
  }
}