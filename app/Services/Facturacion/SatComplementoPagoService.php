<?php

namespace App\Services\Facturacion;

use App\Models\ObraFacturaPago;
use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SatComplementoPagoService
{
    public const RESERVADOS = ['timbrado', 'registrado', 'en_proceso', 'pendiente_confirmacion', 'cancelacion_pendiente'];

    public function generar(array $data, int $actor): SatFacturaPago
    {
        $fecha = Carbon::parse($data['fecha_pago'], 'America/Mexico_City')->utc();
        if ($fecha->isFuture()) {
            throw ValidationException::withMessages(['fecha_pago' => 'La fecha de pago no puede ser futura.']);
        }
        $filas = collect($data['facturas'])->sortBy('factura_id')->values();
        $total = $filas->sum(fn ($fila) => $this->centavos($fila['monto']));
        if ($total !== $this->centavos($data['monto'])) {
            throw ValidationException::withMessages(['monto' => 'El total del pago debe coincidir con la suma aplicada a las facturas.']);
        }
        $hash = hash('sha256', json_encode(['actor' => $actor, 'fecha' => $fecha->toIso8601String(),
            'forma' => $data['forma_pago'], 'tipo_cambio' => number_format((float) ($data['tipo_cambio'] ?? 1), 6, '.', ''),
            'facturas' => $filas->map(fn ($fila) => [(int) $fila['factura_id'], $this->centavos($fila['monto'])])->all()], JSON_THROW_ON_ERROR));
        $lock = Cache::lock('sat-complemento:'.$data['solicitud_id'], 1200);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['solicitud_id' => 'Este complemento se está procesando. Espera antes de consultar o reintentar.']);
        }
        $pago = null;
        $enviado = false;
        try {
            $pago = DB::transaction(function () use ($data, $filas, $hash, $total, $fecha) {
                $previo = SatFacturaPago::where('solicitud_id', $data['solicitud_id'])->lockForUpdate()->first();
                if ($previo && $previo->solicitud_hash !== $hash) {
                    throw ValidationException::withMessages(['solicitud_id' => 'La captura cambió. Abre un formulario nuevo; una solicitud previa no puede reutilizarse para otro pago.']);
                }
                if ($previo && $previo->estado !== 'error') {
                    return $previo;
                }
                $facturas = SatFactura::whereIn('id', $filas->pluck('factura_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if ($facturas->count() !== $filas->count()) {
                    throw ValidationException::withMessages(['facturas' => 'Selecciona facturas distintas y existentes.']);
                }
                $base = $facturas->first();
                $moneda = strtoupper($base->moneda ?: 'MXN');
                $cambio = $moneda === 'MXN' ? 1 : ($data['tipo_cambio'] ?? null);
                if ($moneda === 'MXN' && isset($data['tipo_cambio']) && (float) $data['tipo_cambio'] !== 1.0) {
                    throw ValidationException::withMessages(['tipo_cambio' => 'Para pagos en MXN el tipo de cambio debe ser 1.']);
                }
                if (! $cambio || $cambio <= 0) {
                    throw ValidationException::withMessages(['tipo_cambio' => 'Captura el tipo de cambio del pago en pesos mexicanos.']);
                }
                $documentos = [];
                foreach ($filas as $fila) {
                    $factura = $facturas[$fila['factura_id']];
                    if ($factura->estado !== 'timbrada' || $factura->metodo_pago !== 'PPD' || $factura->tipo_comprobante !== 'I'
                        || ! $factura->uuid || ! $factura->facturapi_invoice_id || ! $factura->facturapi_customer_id) {
                        throw ValidationException::withMessages(['facturas' => 'Todas las facturas deben ser de ingreso PPD timbradas, con UUID y datos de FacturAPI.']);
                    }
                    if ((string) $factura->sat_empresa_id !== (string) $base->sat_empresa_id
                        || $factura->facturapi_customer_id !== $base->facturapi_customer_id
                        || ! $factura->receptor_rfc || strtoupper(trim($factura->receptor_rfc)) !== strtoupper(trim((string) $base->receptor_rfc))
                        || (string) $factura->cliente_id !== (string) $base->cliente_id
                        || strtoupper($factura->moneda ?: 'MXN') !== $moneda) {
                        throw ValidationException::withMessages(['facturas' => 'Un solo pago debe reunir facturas del mismo emisor, cliente, RFC receptor y moneda.']);
                    }
                    $aplicados = $factura->pagos()->whereIn('estado', self::RESERVADOS);
                    $saldo = $this->centavos($factura->total) - $this->centavos($aplicados->sum('sat_factura_pago_documentos.importe_aplicado'));
                    $monto = $this->centavos($fila['monto']);
                    if ($monto <= 0 || $monto > $saldo) {
                        throw ValidationException::withMessages(['facturas' => 'El importe de '.$factura->uuid.' excede su saldo disponible; puede existir un complemento en proceso.']);
                    }
                    $parcialidad = (int) $factura->pagos()->whereIn('estado', self::RESERVADOS)
                        ->max('sat_factura_pago_documentos.numero_parcialidad') + 1;
                    $documentos[] = ['sat_factura_id' => $factura->id, 'importe_aplicado' => $monto / 100,
                        'saldo_anterior' => $saldo / 100, 'saldo_insoluto' => ($saldo - $monto) / 100,
                        'numero_parcialidad' => $parcialidad];
                }
                $valores = ['sat_factura_id' => $base->id, 'solicitud_id' => $data['solicitud_id'], 'solicitud_hash' => $hash,
                    'fecha_pago' => $fecha->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'), 'forma_pago' => $data['forma_pago'], 'moneda' => $moneda,
                    'tipo_cambio' => $cambio, 'monto' => $total / 100, 'estado' => 'en_proceso', 'error_message' => null,
                    'facturapi_payload' => null, 'saldo_anterior' => collect($documentos)->sum('saldo_anterior'),
                    'saldo_insoluto' => collect($documentos)->sum('saldo_insoluto'),
                    'numero_parcialidad' => count($documentos) === 1 ? $documentos[0]['numero_parcialidad'] : null];
                $pago = $previo ?: new SatFacturaPago;
                $pago->fill($valores)->save();
                $pago->documentos()->delete();
                $pago->documentos()->createMany($documentos);

                return $pago;
            });
            if (in_array($pago->estado, ['timbrado', 'cancelado', 'cancelacion_pendiente'], true)) {
                return $pago;
            }
            $pago->load(['documentos.factura', 'factura']);
            if (! $pago->facturapi_payload) {
                $relacionados = [];
                foreach ($pago->documentos as $documento) {
                    $res = $this->api()->get('https://www.facturapi.io/v2/invoices/'.$documento->factura->facturapi_invoice_id.'/payment-summary',
                        ['amount' => (float) $documento->importe_aplicado]);
                    if (! $res->successful()) {
                        throw new RuntimeException('No se pudo consultar el resumen de '.$documento->factura->uuid.': '.$this->error($res));
                    }
                    $resumen = $res->json();
                    if (strtoupper((string) ($resumen['uuid'] ?? '')) !== strtoupper($documento->factura->uuid)
                        || ! isset($resumen['amount'], $resumen['last_balance'], $resumen['installment'])
                        || $this->centavos($resumen['amount']) !== $this->centavos($documento->importe_aplicado)
                        || $this->centavos($resumen['last_balance']) !== $this->centavos($documento->saldo_anterior)
                        || (int) $resumen['installment'] < 1) {
                        throw new RuntimeException('El saldo o resumen de FacturAPI no coincide con el registro local de '.$documento->factura->uuid.'. Revisa sus complementos antes de timbrar.');
                    }
                    $documento->update(['numero_parcialidad' => (int) $resumen['installment'], 'facturapi_resumen' => $resumen]);
                    $relacionados[] = Arr::only($resumen, ['uuid', 'amount', 'installment', 'last_balance', 'taxes', 'currency', 'folio_number', 'series']);
                }
                $pago->update(['facturapi_payload' => ['type' => 'P', 'customer' => $pago->factura->facturapi_customer_id,
                    'idempotency_key' => 'sirico-rep-'.$pago->solicitud_id, 'external_id' => 'sirico-rep-'.$pago->id,
                    'complements' => [['type' => 'pago', 'data' => [['payment_form' => $pago->forma_pago,
                        'currency' => $pago->moneda, 'exchange' => (float) $pago->tipo_cambio,
                        'date' => $pago->fecha_pago->toIso8601String(), 'related_documents' => $relacionados]]]]]]);
            }
            $enviado = true;
            $consultando = (bool) $pago->facturapi_invoice_id;
            $res = $consultando
                ? $this->api()->get('https://www.facturapi.io/v2/invoices/'.$pago->facturapi_invoice_id)
                : $this->api()->post('https://www.facturapi.io/v2/invoices', $pago->facturapi_payload);
            if (! $res->successful()) {
                if (! $consultando && $res->status() >= 400 && $res->status() < 500 && ! in_array($res->status(), [408, 409, 429], true)) {
                    $enviado = false;
                }
                throw new RuntimeException($this->error($res));
            }
            $body = $res->json();
            $uuid = data_get($body, 'uuid') ?: data_get($body, 'stamp.uuid');
            $confirmado = $res->status() !== 202 && ($body['status'] ?? '') === 'valid' && $uuid && ! empty($body['id']);
            DB::transaction(function () use ($pago, $body, $uuid, $confirmado) {
                $pago->update(['facturapi_invoice_id' => $body['id'] ?? $pago->facturapi_invoice_id, 'uuid' => $uuid,
                    'estado' => $confirmado ? 'timbrado' : 'pendiente_confirmacion', 'facturapi_response' => $body,
                    'numero_parcialidad' => $pago->documentos->count() === 1 ? $pago->documentos->first()->numero_parcialidad : null,
                    'error_message' => $confirmado ? null : 'FacturAPI todavía no confirma el timbrado. Consulta este mismo complemento; no generes otro.']);
                if ($confirmado) {
                    $this->ligarPagosInternos($pago);
                }
            });
        } catch (\Throwable $error) {
            if ($pago) {
                $pago->refresh();
            }
            if ($pago && ! in_array($pago->estado, ['timbrado', 'cancelado'], true)) {
                $pago->update(['estado' => $enviado ? 'pendiente_confirmacion' : 'error', 'error_message' => $error->getMessage()]);
            }
            if ($enviado && $pago) {
                return $pago->refresh();
            }
            throw $error;
        } finally {
            $lock->release();
        }
        if ($pago->estado === 'timbrado') {
            $this->descargarArchivos($pago);
        }

        return $pago->refresh();
    }

    public function descargarArchivos(SatFacturaPago $pago): void
    {
        foreach (['xml', 'pdf'] as $formato) {
            try {
                $res = $this->api()->get('https://www.facturapi.io/v2/invoices/'.$pago->facturapi_invoice_id.'/'.$formato);
                if ($res->successful()) {
                    $ruta = 'sat/facturas/pagos/'.$pago->id.'/pago-'.$pago->id.'.'.$formato;
                    if (Storage::disk('local')->put($ruta, $res->body())) {
                        $pago->update([$formato.'_path' => $ruta]);
                    }
                }
            } catch (\Throwable $error) {
                // Un problema de descarga no convierte un REP ya timbrado en un error de emisión.
                report($error);
            }
        }
    }

    private function ligarPagosInternos(SatFacturaPago $pago): void
    {
        foreach ($pago->documentos as $documento) {
            $internos = ObraFacturaPago::where('requiere_complemento_pago', true)->whereNull('sat_factura_pago_id')
                ->where('factura_uuid', $documento->factura->uuid)->whereDate('fecha_pago', $pago->fecha_pago->toDateString())
                ->orderBy('id')->lockForUpdate()->get();
            if ($internos->isNotEmpty() && $internos->sum(fn ($interno) => $this->centavos($interno->monto)) === $this->centavos($documento->importe_aplicado)) {
                ObraFacturaPago::whereIn('id', $internos->pluck('id'))->whereNull('sat_factura_pago_id')->update(['sat_factura_pago_id' => $pago->id]);
            }
        }
    }

    private function centavos(mixed $valor): int { return (int) round((float) $valor * 100); }
    private function api() { return Http::withBasicAuth(config('services.facturapi.secret_key'), '')->acceptJson()->connectTimeout(10)->timeout(45); }
    private function error($response): string { return (string) ($response->json('message') ?: 'FacturAPI respondió HTTP '.$response->status().'.'); }
}
