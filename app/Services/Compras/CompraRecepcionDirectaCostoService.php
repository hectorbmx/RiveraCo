<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use Illuminate\Validation\ValidationException;

class CompraRecepcionDirectaCostoService
{
    public function calcular(OrdenCompra $orden, OrdenCompraDetalle $detalle, float $cantidad, float $factor = 1, array $acumulado = []): array
    {
        $ordenada = (float) $detalle->cantidad;
        $precio = (float) $detalle->precio_unitario;
        $descuento = $detalle->descuento_importe !== null
            ? (float) $detalle->descuento_importe
            : $precio * $ordenada * (float) $detalle->descuento_porcentaje / 100;
        $neto = $detalle->importe !== null ? (float) $detalle->importe : $precio * $ordenada - $descuento;
        $moneda = strtoupper((string) ($orden->moneda ?: 'MXN'));
        $tipoCambio = $moneda === 'MXN' ? 1.0 : (float) ($detalle->tipo_cambio ?: $orden->tipo_cambio);

        if ($ordenada <= 0 || $cantidad <= 0 || $factor <= 0 || $precio < 0 || $neto < 0
            || $tipoCambio <= 0 || ! is_finite($tipoCambio) || ! is_finite($neto)) {
            throw ValidationException::withMessages(['costo' => 'La partida debe tener cantidad, costo neto y tipo de cambio válidos en la OC.']);
        }

        $unitario = $neto / $ordenada;
        $cantidadAcumulada = (float) ($acumulado['cantidad'] ?? 0) + $cantidad;
        $importe = round(round($unitario * $cantidadAcumulada, 2) - (float) ($acumulado['importe_oc'] ?? 0), 2);
        $importeMxn = round(round($unitario * $cantidadAcumulada * $tipoCambio, 2) - (float) ($acumulado['importe_mxn'] ?? 0), 2);
        $unitarioMxn = $unitario * $tipoCambio / $factor;
        if ($unitarioMxn > 9999999999.9999) {
            throw ValidationException::withMessages(['costo' => 'El costo unitario excede la precisión admitida por inventario.']);
        }

        return [
            'precio_unitario_oc' => $precio, 'descuento_oc' => max(0, $precio * $ordenada - $neto),
            'importe_neto_oc' => $neto, 'costo_unitario_oc' => $unitario,
            'costo_unitario_mxn' => round($unitarioMxn, 8), 'tipo_cambio' => $tipoCambio,
            'importe_oc' => $importe, 'importe_mxn' => $importeMxn,
        ];
    }
}
