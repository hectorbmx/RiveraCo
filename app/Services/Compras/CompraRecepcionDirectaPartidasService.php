<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use Illuminate\Support\Collection;

class CompraRecepcionDirectaPartidasService
{
    public function pendientes(OrdenCompra $orden): Collection
    {
        $orden->loadMissing('detalles.producto');

        return $orden->detalles->map(function ($detalle) {
            $ordenado = (float) $detalle->cantidad;
            $recibido = (float) $detalle->cantidad_recibida;

            return [
                'id' => (int) $detalle->id,
                'producto_id' => $detalle->producto_id,
                'descripcion' => $detalle->descripcion ?: $detalle->producto?->nombre,
                'unidad' => $detalle->unidad,
                'ordenado' => $ordenado,
                'recibido' => $recibido,
                'pendiente' => round(max(0, $ordenado - $recibido), 3),
            ];
        })->filter(fn (array $partida) => $partida['pendiente'] > 0)->values();
    }
}
