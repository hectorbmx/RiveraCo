<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraRecepcionEstadoService
{
    public function sincronizar(OrdenCompra $orden): void
    {
        DB::transaction(function () use ($orden) {
            $actual = OrdenCompra::query()->whereKey($orden->id)->lockForUpdate()->firstOrFail();
            $detalles = $actual->detalles()->orderBy('id')->lockForUpdate()->get();
            $hayRecepcion = false;
            $completa = $detalles->isNotEmpty();
            foreach ($detalles as $detalle) {
                $cantidad = max(0, (float) $detalle->cantidad);
                $recibida = max(0, (float) $detalle->cantidad_recibida);
                $estado = $recibida <= 0 ? 'pendiente' : ($recibida + 0.0005 >= $cantidad ? 'recibida' : 'parcial');
                if ($detalle->estado_recepcion !== $estado) $detalle->forceFill(['estado_recepcion' => $estado])->save();
                $hayRecepcion = $hayRecepcion || $recibida > 0;
                if ($recibida + 0.0005 < $cantidad) $completa = false;
            }
            $estado = ! $hayRecepcion ? 'pendiente' : ($completa ? 'recibida' : 'parcial');
            if ($actual->estado_recepcion !== $estado) $actual->forceFill(['estado_recepcion' => $estado])->save();
            $orden->estado_recepcion = $estado;
            $orden->syncOriginalAttribute('estado_recepcion');
            $orden->setRelation('detalles', $detalles);
        });
    }
}
