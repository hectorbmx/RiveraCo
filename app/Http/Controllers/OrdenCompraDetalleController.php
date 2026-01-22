<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdenCompraDetalleRequest;
use App\Http\Requests\UpdateOrdenCompraDetalleRequest;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Services\OrdenCompraTotalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class OrdenCompraDetalleController extends Controller
{
    public function store(StoreOrdenCompraDetalleRequest $request, $ordenCompraId)
    {
        $oc = OrdenCompra::findOrFail($ordenCompraId);

        if (in_array($oc->estado_normalizado, ['autorizada','cancelada'], true)) {
            return back()->with('error', 'No puedes modificar detalles en una orden autorizada o cancelada.');
        }
        logger()->info('OC Detalle request', $request->all());

        return DB::transaction(function () use ($request, $oc) {

            $cantidad = (float)$request->cantidad;
            $precio   = (float)$request->precio_unitario;

            // Si no viene importe, lo calculamos
            $importe = $request->filled('importe')
                ? (float)$request->importe
                : round($cantidad * $precio, 2);
            $detalle = new OrdenCompraDetalle();
            $detalle->orden_compra_id = $oc->id;
            $detalle->producto_id     = $request->producto_id;
            $detalle->legacy_prod_id  = $request->legacy_prod_id;
            $detalle->descripcion     = $request->descripcion;
            $detalle->unidad          = $request->unidad;
            $detalle->cantidad        = $cantidad;
            $detalle->precio_unitario = $precio;
            $detalle->importe         = $importe;
            $detalle->iva             = $request->filled('iva') ? (float)$request->iva : 0;
            $detalle->retenciones     = $request->filled('retenciones') ? (float)$request->retenciones : 0;
            $detalle->otros_impuestos = $request->filled('otros_impuestos') ? (float)$request->otros_impuestos : 0;
            // tipo_cambio por línea (si quieres copiar del encabezado)
            if (is_null($detalle->tipo_cambio)) {
                $detalle->tipo_cambio = $oc->tipo_cambio;
            }
            $detalle->notas           = $request->notas;
            $detalle->save();
            logger()->info('OC Detalle guardado', [
                'oc_id' => $oc->id,
                'detalle_id' => $detalle->id,
                'proveedor_id' => $oc->proveedor_id,
                'producto_id' => $detalle->producto_id,
                'legacy_prod_id' => $detalle->legacy_prod_id,
                'descripcion' => $detalle->descripcion,
                'precio_unitario' => $detalle->precio_unitario,
            ]);

            $this->syncProductoProveedorDesdeDetalle($oc, $detalle);
            OrdenCompraTotalesService::recalcular($oc);
            return back()->with('success', 'Detalle agregado y totales recalculados.');
        });
    }


private function syncProductoProveedorDesdeDetalle(OrdenCompra $oc, OrdenCompraDetalle $detalle): void
{
    $proveedorId = (int) $oc->proveedor_id;
    $productoId  = (int) $detalle->producto_id;

    if (!$proveedorId || !$productoId) return;

    $precio = (float) $detalle->precio_unitario;
    $moneda = (string) ($oc->moneda ?? 'MXN');

    // registro actual del pivot (si existe)
    $actual = DB::table('producto_proveedor')
        ->where('proveedor_id', $proveedorId)
        ->where('producto_id', $productoId)
        ->first();

    // Si no existe: crear pivot + historial
    if (!$actual) {
        DB::table('producto_proveedor')->insert([
            'proveedor_id' => $proveedorId,
            'producto_id' => $productoId,
            'precio_lista' => $precio,
            'moneda' => $moneda,
            'tiempo_entrega_dias' => null,
            'activo' => 1,
            'notas' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Historial (si ya creaste la tabla)
        if (Schema::hasTable('producto_proveedor_precios')) {
            DB::table('producto_proveedor_precios')->insert([
                'proveedor_id' => $proveedorId,
                'producto_id' => $productoId,
                'precio' => $precio,
                'moneda' => $moneda,
                'orden_compra_id' => $oc->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return;
    }

    // Si existe: comparar y actualizar si cambió
    $precioActual = (float) $actual->precio_lista;
    $monedaActual = (string) ($actual->moneda ?? 'MXN');

    $cambio = ($precioActual != $precio) || ($monedaActual !== $moneda);

    if ($cambio) {
        DB::table('producto_proveedor')
            ->where('proveedor_id', $proveedorId)
            ->where('producto_id', $productoId)
            ->update([
                'precio_lista' => $precio,
                'moneda' => $moneda,
                'updated_at' => now(),
            ]);

        // Historial (si ya creaste la tabla)
        if (Schema::hasTable('producto_proveedor_precios')) {
            // opcional: evitar duplicar el mismo precio consecutivo
            $ultimo = DB::table('producto_proveedor_precios')
                ->where('proveedor_id', $proveedorId)
                ->where('producto_id', $productoId)
                ->orderByDesc('id')
                ->first();

            $duplicado = $ultimo && ((float)$ultimo->precio == $precio) && ((string)$ultimo->moneda === $moneda);

            if (!$duplicado) {
                DB::table('producto_proveedor_precios')->insert([
                    'proveedor_id' => $proveedorId,
                    'producto_id' => $productoId,
                    'precio' => $precio,
                    'moneda' => $moneda,
                    'orden_compra_id' => $oc->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

    public function update(UpdateOrdenCompraDetalleRequest $request, $ordenCompraId, $detalleId)
    {
        $oc = OrdenCompra::findOrFail($ordenCompraId);

        if (in_array($oc->estado_normalizado, ['autorizada','cancelada'], true)) {
            return back()->with('error', 'No puedes modificar detalles en una orden autorizada o cancelada.');
        }

        $detalle = OrdenCompraDetalle::where('orden_compra_id', $oc->id)->findOrFail($detalleId);

        return DB::transaction(function () use ($request, $oc, $detalle) {

            $cantidad = (float)$request->cantidad;
            $precio   = (float)$request->precio_unitario;

            $importe = $request->filled('importe')
                ? (float)$request->importe
                : round($cantidad * $precio, 2);

            $detalle->producto_id     = $request->producto_id;
            $detalle->legacy_prod_id  = $request->legacy_prod_id;

            $detalle->descripcion     = $request->descripcion;
            $detalle->unidad          = $request->unidad;

            $detalle->cantidad        = $cantidad;
            $detalle->precio_unitario = $precio;

            $detalle->importe         = $importe;
            $detalle->iva             = $request->filled('iva') ? (float)$request->iva : 0;
            $detalle->retenciones     = $request->filled('retenciones') ? (float)$request->retenciones : 0;
            $detalle->otros_impuestos = $request->filled('otros_impuestos') ? (float)$request->otros_impuestos : 0;

            $detalle->notas           = $request->notas;

            // si quieres forzar que siempre siga el tipo_cambio del encabezado:
            $detalle->tipo_cambio = $oc->tipo_cambio;

            $detalle->save();

            OrdenCompraTotalesService::recalcular($oc);

            return back()->with('success', 'Detalle actualizado y totales recalculados.');
        });
    }

    public function destroy(Request $request, $ordenCompraId, $detalleId)
    {
        $oc = OrdenCompra::findOrFail($ordenCompraId);

        if (in_array($oc->estado_normalizado, ['autorizada','cancelada'], true)) {
            return back()->with('error', 'No puedes modificar detalles en una orden autorizada o cancelada.');
        }

        $detalle = OrdenCompraDetalle::where('orden_compra_id', $oc->id)->findOrFail($detalleId);

        return DB::transaction(function () use ($oc, $detalle) {
            $detalle->delete();
            OrdenCompraTotalesService::recalcular($oc);
            return back()->with('success', 'Detalle eliminado y totales recalculados.');
        });
    }
}
