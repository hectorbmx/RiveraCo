<?php

namespace App\Services\Facturacion;

use App\Models\SatFactura;
use App\Models\SatFacturaBorrador;
use App\Models\SatFacturaConcepto;
use Facturapi\Exceptions\FacturapiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotaCreditoTimbradoService
{
    public function __construct(private NotaCreditoPreviewService $preview, private FacturapiService $facturapi) {}

    public function emitir(SatFacturaBorrador $borrador, int $usuario): string
    {
        $this->preview->verificarEmisor($borrador, $this->facturapi);
        [$payload, $id] = DB::transaction(function () use ($borrador, $usuario) {
            $actual = SatFacturaBorrador::whereKey($borrador->id)->lockForUpdate()->firstOrFail();
            if ($actual->tipo_comprobante !== 'E' || $actual->estado !== 'borrador' || $actual->sat_factura_id) {
                throw ValidationException::withMessages(['nota' => 'La nota ya no está disponible para timbrar.']);
            }
            $payload = $this->preview->payload($actual);
            $facturaOrigen = SatFactura::whereKey($actual->payload['factura_relacionada_id'])->lockForUpdate()->firstOrFail();
            $aplicado = (float) DB::table('sat_factura_relaciones')
                ->join('sat_facturas', 'sat_facturas.id', '=', 'sat_factura_relaciones.sat_factura_id')
                ->where('sat_factura_relaciones.factura_relacionada_id', $facturaOrigen->id)
                ->where('sat_facturas.tipo_comprobante', 'E')->whereIn('sat_facturas.estado', ['timbrada', 'cancelacion_solicitada'])
                ->sum('sat_facturas.total');
            $reservado = SatFacturaBorrador::where('tipo_comprobante', 'E')
                ->whereIn('estado', ['enviando', 'pendiente_facturapi', 'revision_emision'])->whereNull('sat_factura_id')
                ->get(['id', 'payload'])->filter(fn ($otro) => (int) ($otro->payload['factura_relacionada_id'] ?? 0) === $facturaOrigen->id)
                ->sum(fn ($otro) => (float) ($otro->payload['total'] ?? 0));
            if (round($aplicado + $reservado + (float) $actual->payload['total'], 2) > (float) $facturaOrigen->total + 0.001) {
                throw ValidationException::withMessages(['nota' => 'El importe supera el saldo de la factura después de otras notas timbradas o en proceso.']);
            }
            $clave = (string) Str::uuid();
            $payload['idempotency_key'] = $clave;
            $payload['external_id'] = 'sirico-nota-'.$actual->id.'-'.$clave;
            $datos = $actual->payload;
            $datos['_emision'] = ['clave' => $clave, 'external_id' => $payload['external_id'], 'usuario' => $usuario, 'solicitud' => $payload];
            $actual->update(['payload' => $datos, 'estado' => 'enviando']);
            return [$payload, $actual->id];
        });

        try {
            $invoice = $this->facturapi->client()->Invoices->create($payload);
        } catch (\Throwable $e) {
            $rechazo = $e instanceof FacturapiException && $e->getStatusCode() === 400;
            DB::transaction(function () use ($id, $rechazo) {
                $actual = SatFacturaBorrador::whereKey($id)->lockForUpdate()->firstOrFail();
                if ($actual->estado === 'enviando') $actual->update(['estado' => $rechazo ? 'borrador' : 'revision_emision']);
            });
            report($e);
            return $rechazo ? 'rechazada' : 'revision_emision';
        }

        $respuesta = json_decode(json_encode($invoice, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($id, $respuesta) {
            $actual = SatFacturaBorrador::whereKey($id)->lockForUpdate()->firstOrFail();
            $datos = $actual->payload;
            $datos['_emision']['respuesta'] = $respuesta;
            $actual->update(['payload' => $datos, 'estado' => 'pendiente_facturapi']);
        });
        return $this->registrarRespuesta($id);
    }

    public function consultar(SatFacturaBorrador $borrador): string
    {
        if ($borrador->tipo_comprobante !== 'E' || $borrador->estado !== 'pendiente_facturapi') {
            throw ValidationException::withMessages(['nota' => 'La nota no tiene una emisión pendiente consultable.']);
        }
        $this->preview->verificarEmisor($borrador, $this->facturapi);
        $idRemoto = $borrador->payload['_emision']['respuesta']['id'] ?? null;
        if (! $idRemoto) throw ValidationException::withMessages(['nota' => 'No se recibió el identificador de Facturapi; la emisión necesita revisión.']);
        $respuesta = json_decode(json_encode($this->facturapi->client()->Invoices->retrieve($idRemoto), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($borrador, $respuesta, $idRemoto) {
            $actual = SatFacturaBorrador::whereKey($borrador->id)->lockForUpdate()->firstOrFail();
            if ($actual->estado !== 'pendiente_facturapi' || ($actual->payload['_emision']['respuesta']['id'] ?? null) !== $idRemoto) return;
            $datos = $actual->payload;
            $datos['_emision']['respuesta'] = $respuesta;
            $actual->update(['payload' => $datos]);
        });
        return $this->registrarRespuesta($borrador->id);
    }

    private function registrarRespuesta(int $id): string
    {
        $resultado = DB::transaction(function () use ($id) {
            $b = SatFacturaBorrador::with(['cliente', 'empresa'])->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($b->sat_factura_id) return ['estado' => 'timbrada', 'factura' => $b->sat_factura_id];
            if ($b->estado !== 'pendiente_facturapi') return ['estado' => $b->estado];
            $datos = $b->payload;
            $respuesta = $datos['_emision']['respuesta'] ?? [];
            if (($respuesta['status'] ?? '') === 'pending') return ['estado' => 'pendiente_facturapi'];
            if (($respuesta['status'] ?? '') !== 'valid' || empty($respuesta['id']) || empty($respuesta['uuid'])
                || ($respuesta['type'] ?? '') !== 'E' || (int) round((float) ($respuesta['total'] ?? -1) * 100) !== (int) round((float) $datos['total'] * 100)) {
                $b->update(['estado' => 'revision_emision']);
                return ['estado' => 'revision_emision'];
            }
            $factura = SatFactura::create([
                'sat_empresa_id' => $b->sat_empresa_id, 'cliente_id' => $b->cliente_id, 'obra_id' => $b->obra_id,
                'facturapi_invoice_id' => $respuesta['id'], 'uuid' => $respuesta['uuid'],
                'serie' => $respuesta['series'] ?? null, 'folio' => $respuesta['folio_number'] ?? null,
                'tipo_comprobante' => 'E', 'cfdi_version' => $respuesta['cfdi_version'] ?? '4.0',
                'receptor_rfc' => $b->cliente->rfc, 'receptor_nombre' => $b->cliente->razon_social ?: $b->cliente->nombre_comercial,
                'receptor_regimen' => $b->cliente->regimen_fiscal, 'receptor_cp' => $b->cliente->codigo_postal,
                'uso_cfdi' => 'G02', 'metodo_pago' => 'PUE', 'forma_pago' => '30', 'moneda' => 'MXN',
                'subtotal' => $datos['subtotal'], 'iva' => $datos['iva'], 'total' => $datos['total'],
                'estado' => 'timbrada', 'timbrado_por' => $datos['_emision']['usuario'],
                'fecha_emision' => $respuesta['date'] ?? now(), 'fecha_timbrado' => $respuesta['stamp']['date'] ?? now(),
                'facturapi_response' => $respuesta,
            ]);
            $producto = $datos['_emision']['solicitud']['items'][0]['product'];
            SatFacturaConcepto::create([
                'sat_factura_id' => $factura->id, 'descripcion' => $producto['description'], 'cantidad' => 1,
                'unidad' => 'Actividad', 'clave_producto_servicio' => '84111506', 'clave_unidad' => 'ACT',
                'precio_unitario' => $datos['subtotal'], 'subtotal' => $datos['subtotal'], 'iva' => $datos['iva'],
                'total' => $datos['total'], 'taxes' => $producto['taxes'], 'facturapi_payload' => $producto,
            ]);
            DB::table('sat_factura_relaciones')->insert([
                'sat_factura_id' => $factura->id, 'factura_relacionada_id' => $datos['factura_relacionada_id'],
                'tipo_relacion' => '07', 'uuid_relacionado' => $datos['uuid_relacionado'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $b->update(['sat_factura_id' => $factura->id, 'estado' => 'timbrado']);
            return ['estado' => 'timbrada', 'factura' => $factura->id];
        });
        if (($resultado['estado'] ?? '') === 'timbrada') $this->descargarArchivos($resultado['factura']);
        return $resultado['estado'];
    }

    private function descargarArchivos(int $id): void
    {
        $factura = SatFactura::with('empresa')->findOrFail($id);
        try {
            $folder = 'sat/facturas/'.$factura->empresa->rfc.'/'.$factura->uuid;
            $xmlPath = $folder.'/'.$factura->uuid.'.xml';
            $pdfPath = $folder.'/'.$factura->uuid.'.pdf';
            Storage::put($xmlPath, $this->facturapi->client()->Invoices->downloadXml($factura->facturapi_invoice_id));
            Storage::put($pdfPath, $this->facturapi->client()->Invoices->downloadPdf($factura->facturapi_invoice_id));
            $factura->update(['xml_path' => $xmlPath, 'pdf_path' => $pdfPath]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
