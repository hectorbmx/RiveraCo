<?php

namespace App\Services\Facturacion;

use App\Models\SatFactura;
use App\Models\SatFacturaBorrador;
use Illuminate\Validation\ValidationException;

class NotaCreditoPreviewService
{
    public function verificarEmisor(SatFacturaBorrador $borrador, FacturapiService $facturapi): void
    {
        $empresa = $borrador->empresa;
        if (! $empresa || ! $empresa->activo || ! trim((string) $empresa->rfc)) {
            throw ValidationException::withMessages(['nota' => 'La empresa emisora debe estar activa y tener RFC.']);
        }
        $organizacion = $facturapi->client()->Organizations->retrieve('me');
        $rfcFacturapi = strtoupper(trim((string) ($organizacion->legal->tax_id ?? $organizacion->tax_id ?? '')));
        if ($rfcFacturapi === '' || $rfcFacturapi !== strtoupper(trim((string) $empresa->rfc))) {
            throw ValidationException::withMessages(['nota' => 'El RFC de la empresa no coincide con el emisor de la llave configurada en Facturapi.']);
        }
    }

    public function payload(SatFacturaBorrador $borrador): array
    {
        $datos = $borrador->payload ?? [];
        if (($datos['motivo'] ?? '') !== 'anticipo' || ($datos['tipo_relacion'] ?? '') !== '07') {
            throw ValidationException::withMessages(['nota' => 'La previsualización está disponible por ahora para aplicación de anticipo.']);
        }
        if (! in_array($datos['tipo_iva'] ?? null, ['0.16', 'exento', 'sin_iva'], true)) {
            throw ValidationException::withMessages(['nota' => 'Edita el borrador y selecciona el tipo de IVA.']);
        }
        $factura = SatFactura::query()->whereKey($datos['factura_relacionada_id'] ?? null)
            ->where('sat_empresa_id', $borrador->sat_empresa_id)->where('cliente_id', $borrador->cliente_id)
            ->where('tipo_comprobante', 'I')->where('estado', 'timbrada')
            ->where('uuid', $datos['uuid_relacionado'] ?? '')->first();
        if (! $factura) {
            throw ValidationException::withMessages(['nota' => 'La factura relacionada ya no está disponible como ingreso timbrado del cliente y la empresa.']);
        }
        $cliente = $borrador->cliente;
        if (! $cliente || ! $cliente->activo || ! trim((string) $cliente->rfc)
            || ! trim((string) ($cliente->razon_social ?: $cliente->nombre_comercial))
            || ! trim((string) $cliente->regimen_fiscal) || ! trim((string) $cliente->codigo_postal)) {
            throw ValidationException::withMessages(['nota' => 'El cliente debe estar activo y tener RFC, nombre, régimen fiscal y código postal.']);
        }
        if (strtoupper(trim((string) $cliente->rfc)) !== strtoupper(trim((string) $factura->receptor_rfc))) {
            throw ValidationException::withMessages(['nota' => 'El RFC actual del cliente no coincide con la factura relacionada.']);
        }
        if (($datos['moneda'] ?? '') !== 'MXN' || $factura->moneda !== 'MXN') {
            throw ValidationException::withMessages(['nota' => 'La previsualización de notas en moneda extranjera aún no está disponible.']);
        }
        $subtotal = (float) ($datos['subtotal'] ?? 0);
        $iva = ($datos['tipo_iva'] === '0.16') ? round($subtotal * 0.16, 2) : 0.0;
        $total = round($subtotal + $iva, 2);
        if ($subtotal <= 0 || ! isset($datos['iva'], $datos['total'])
            || abs((float) $datos['iva'] - $iva) > 0.001 || abs((float) $datos['total'] - $total) > 0.001) {
            throw ValidationException::withMessages(['nota' => 'Edita el borrador para actualizar y revisar el IVA y el total.']);
        }
        if ($total > (float) $factura->total + 0.001) {
            throw ValidationException::withMessages(['nota' => 'El total de la nota supera el total de la factura relacionada. Revisa el importe antes de impuestos.']);
        }
        $taxes = match ($datos['tipo_iva']) {
            '0.16' => [['type' => 'IVA', 'rate' => 0.16]],
            'exento' => [['type' => 'IVA', 'rate' => 0, 'factor' => 'Exento']],
            default => [],
        };

        return [
            'type' => 'E',
            'customer' => [
                'legal_name' => $cliente->razon_social ?: $cliente->nombre_comercial,
                'tax_id' => strtoupper(trim($cliente->rfc)),
                'tax_system' => $cliente->regimen_fiscal,
                'address' => ['zip' => $cliente->codigo_postal],
            ],
            'items' => [[
                'quantity' => 1,
                'product' => [
                    'description' => 'Aplicación de anticipo',
                    'product_key' => '84111506', 'unit_key' => 'ACT', 'unit_name' => 'Actividad',
                    'price' => $subtotal, 'tax_included' => false,
                    'taxability' => $datos['tipo_iva'] === 'sin_iva' ? '01' : '02',
                    'taxes' => $taxes,
                ],
            ]],
            'payment_form' => '30',
            'payment_method' => 'PUE',
            'use' => 'G02',
            'currency' => 'MXN',
            'related_documents' => [['relationship' => '07', 'documents' => [$factura->uuid]]],
        ];
    }
}
