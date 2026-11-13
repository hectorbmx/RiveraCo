<?php

namespace App\Services\Compras;

use App\Models\Obra;
use App\Models\OrdenCompra;
use App\Support\Inventario\UnidadMedidaCatalogo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompraRecepcionDirectaValidacionService
{
    public function validar(OrdenCompra $orden, array $partidas): Collection
    {
        Validator::make(['partidas' => $partidas], [
            'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.orden_compra_detalle_id' => ['required', 'integer', 'min:1', 'distinct'],
            'partidas.*.cantidad_recibida' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
        ])->validate();

        if ($orden->modalidad_entrega !== OrdenCompra::ENTREGA_OBRA_DIRECTA
            || strtoupper((string) $orden->estado) !== 'AUTORIZADA') {
            $this->error('orden_compra', 'La recepción directa requiere una OC autorizada con entrega directa a obra.');
        }

        $orden->loadMissing(['obra', 'almacenDestino', 'detalles.producto']);
        if (! $orden->obra || ! in_array((int) $orden->obra->estatus_nuevo,
            [Obra::ESTATUS_PLANEACION, Obra::ESTATUS_EJECUCION], true)) {
            $this->error('obra', 'La obra fijada en la OC ya no está disponible para recibir material.');
        }
        $almacen = $orden->almacenDestino;
        if (! $almacen || ! $almacen->activo || ! app(AreaAlmacenCompraService::class)->permite($orden->area_id ? (int) $orden->area_id : null, $almacen->area_id ? (int) $almacen->area_id : null)) {
            $this->error('almacen', 'El almacén fijado en la OC no está activo o no corresponde al área de la orden.');
        }

        $esHuentitan = strtoupper((string) $almacen->codigo) === 'AL-HUENTITAN';
        $detalles = $orden->detalles->keyBy('id');

        return collect($partidas)->map(function (array $partida, $index) use ($detalles, $orden, $esHuentitan) {
            $campo = 'partidas.'.$index;
            $detalle = $detalles->get((int) $partida['orden_compra_detalle_id']);
            if (! $detalle || (int) $detalle->orden_compra_id !== (int) $orden->id) {
                $this->error($campo, 'La partida no pertenece a esta orden de compra.');
            }
            $producto = $detalle->producto;
            if (! $producto || ! $producto->activo) {
                $this->error($campo, 'La partida requiere un producto de catálogo activo.');
            }
            if (str_starts_with(strtoupper((string) $producto->sku), 'HUE-') !== $esHuentitan) {
                $this->error($campo, 'El producto no corresponde al catálogo del almacén responsable.');
            }
            $cantidad = (float) $partida['cantidad_recibida'];
            $saldo = round(max(0, (float) $detalle->cantidad - (float) $detalle->cantidad_recibida), 3);
            if (! is_finite($cantidad) || $cantidad > $saldo + 0.000001) {
                $this->error($campo.'.cantidad_recibida', 'La cantidad recibida excede el saldo pendiente de la OC.');
            }

            $unidadBase = UnidadMedidaCatalogo::normalizar($producto->unidad_base ?: $producto->unidad);
            $unidadOc = UnidadMedidaCatalogo::normalizar($detalle->unidad) ?: $unidadBase;
            $factor = 1.0;
            if ($unidadBase && UnidadMedidaCatalogo::equivalente($unidadOc) !== UnidadMedidaCatalogo::equivalente($unidadBase)) {
                $unidadCompra = UnidadMedidaCatalogo::normalizar($producto->unidad_compra);
                $factor = (float) $producto->cantidad_por_unidad_compra;
                if (UnidadMedidaCatalogo::equivalente($unidadOc) !== UnidadMedidaCatalogo::equivalente($unidadCompra) || ! is_finite($factor) || $factor <= 0) {
                    $this->error($campo, 'La unidad de compra necesita una conversión configurada a la unidad de inventario.');
                }
            }
            $cantidadInventario = $cantidad * $factor;
            if (! is_finite($cantidadInventario) || $cantidadInventario <= 0
                || abs($cantidadInventario - round($cantidadInventario, 3)) > 0.000001) {
                $this->error($campo, 'La cantidad convertida debe poder registrarse con hasta tres decimales de inventario.');
            }

            return [
                'detalle' => $detalle, 'cantidad' => $cantidad,
                'factor_inventario' => $factor, 'cantidad_inventario' => round($cantidadInventario, 3),
                'unidad_inventario' => $unidadBase ?: $unidadOc,
            ];
        })->values();
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
