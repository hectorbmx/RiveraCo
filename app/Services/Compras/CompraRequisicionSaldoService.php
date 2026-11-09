<?php

namespace App\Services\Compras;

use App\Models\ObraRequisicion;
use App\Models\ObraRequisicionDetalle;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Support\Inventario\UnidadMedidaCatalogo;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompraRequisicionSaldoService
{
    public const ESTADOS_SURTIBLES = ['autorizada', 'en_surtido', 'surtida_parcial'];

    // Invocar dentro de la transacción de OC/recepción. Orden global: cabeceras y después partidas.
    private function bloquear(Collection $ids): Collection
    {
        $cabeceras = ObraRequisicionDetalle::whereIn('id', $ids)->pluck('obra_requisicion_id');
        $requisiciones = ObraRequisicion::whereIn('id', $cabeceras)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        return ObraRequisicionDetalle::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()
            ->each(fn ($detalle) => $detalle->setRelation('requisicion', $requisiciones->get($detalle->obra_requisicion_id)))
            ->keyBy('id');
    }

    private function comprobar(OrdenCompra $orden, OrdenCompraDetalle $compra, ?ObraRequisicionDetalle $partida, string $unidad): void
    {
        $requisicion = $partida?->requisicion;
        if ($orden->modalidad_entrega !== OrdenCompra::ENTREGA_OBRA_DIRECTA
            || ! $requisicion || (int) $requisicion->obra_id !== (int) $orden->obra_id
            || $partida->item_type !== 'producto' || (int) $partida->item_id !== (int) $compra->producto_id
            || ! in_array($requisicion->estado, self::ESTADOS_SURTIBLES, true)
            || ! $requisicion->autorizado_por || ! $requisicion->autorizado_at
            || (int) $requisicion->autorizado_por === (int) $requisicion->solicitado_por
            || in_array($partida->estado, ['rechazada', 'cancelada'], true)
            || (float) $partida->cantidad_autorizada <= 0
            || UnidadMedidaCatalogo::equivalente($partida->unidad) !== UnidadMedidaCatalogo::equivalente($unidad)) {
            $this->error('Selecciona una partida de material autorizada por otro usuario, de la misma obra y producto, en unidad base.');
        }
    }

    public function validarVinculo(OrdenCompra $orden, OrdenCompraDetalle $compra): void
    {
        if (! $compra->obra_requisicion_detalle_id) return;
        if ((float) $compra->cantidad_recibida > 0) {
            $this->error('No puedes modificar una partida vinculada que ya tiene recepciones.');
        }
        $partida = $this->bloquear(collect([$compra->obra_requisicion_detalle_id]))->get($compra->obra_requisicion_detalle_id);
        $producto = $compra->producto()->first();
        if (! $producto) $this->error('La requisición requiere un producto de catálogo.');
        $base = UnidadMedidaCatalogo::normalizar($producto->unidad_base ?: $producto->unidad);
        $unidad = UnidadMedidaCatalogo::normalizar($compra->unidad) ?: $base;
        $mismaUnidad = UnidadMedidaCatalogo::equivalente($unidad) === UnidadMedidaCatalogo::equivalente($base);
        $factor = $mismaUnidad ? 1 : (float) $producto->cantidad_por_unidad_compra;
        if (! $mismaUnidad && (UnidadMedidaCatalogo::equivalente($unidad) !== UnidadMedidaCatalogo::equivalente($producto->unidad_compra) || $factor <= 0)) {
            $this->error('Configura la conversión de compra a la unidad base del producto.');
        }
        $this->comprobar($orden, $compra, $partida, $base);
        $this->comprobarSaldo($partida, (float) $compra->cantidad * $factor);
    }

    public function aplicarEntrega(OrdenCompra $orden, Collection $lineas): void
    {
        $vinculadas = $lineas->filter(fn ($linea) => $linea['detalle']->obra_requisicion_detalle_id);
        if ($vinculadas->isEmpty()) return;
        $partidas = $this->bloquear($vinculadas->pluck('detalle.obra_requisicion_detalle_id')->unique());
        foreach ($vinculadas->groupBy(fn ($linea) => $linea['detalle']->obra_requisicion_detalle_id) as $id => $grupo) {
            $partida = $partidas->get($id);
            foreach ($grupo as $linea) $this->comprobar($orden, $linea['detalle'], $partida, $linea['unidad_inventario']);
            $cantidad = round((float) $grupo->sum('cantidad_inventario'), 3);
            $this->comprobarSaldo($partida, $cantidad);
            $partida->cantidad_surtida = round((float) $partida->cantidad_surtida + $cantidad, 3);
            $partida->estado = (float) $partida->cantidad_surtida >= (float) $partida->cantidad_autorizada ? 'surtida' : 'surtida_parcial';
            $partida->save();
        }
    }

    private function comprobarSaldo(ObraRequisicionDetalle $partida, float $cantidad): void
    {
        $saldo = round((float) $partida->cantidad_autorizada - (float) $partida->cantidad_surtida, 3);
        if (! is_finite($cantidad) || $cantidad <= 0 || $cantidad > $saldo + 0.000001) {
            $this->error('La cantidad excede el saldo autorizado pendiente de surtir de la requisición.');
        }
    }

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['obra_requisicion_detalle_id' => $mensaje]);
    }
}
