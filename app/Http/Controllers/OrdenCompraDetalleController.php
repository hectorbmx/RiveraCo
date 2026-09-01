<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdenCompraDetalleRequest;
use App\Http\Requests\UpdateOrdenCompraDetalleRequest;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\ObraCivilInsumo;
use App\Models\TipoRetencion;
use App\Services\OrdenCompraTotalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class OrdenCompraDetalleController extends Controller
{
    public function store(StoreOrdenCompraDetalleRequest $request, $ordenCompraId)
{
    $oc = OrdenCompra::findOrFail($ordenCompraId);

    if (in_array($oc->estado_normalizado, ['autorizada', 'verificada', 'cancelada'], true)) {
        return back()->with(
            'error',
            'No puedes modificar detalles en una orden autorizada, verificada o cancelada.'
        );
    }

    logger()->info('OC Detalle request', $request->all());

    return DB::transaction(function () use ($request, $oc) {
        $cantidad = (float) $request->cantidad;
        $precio = (float) $request->precio_unitario;
        $importes = $this->calcularImportesPartida($request, $cantidad, $precio);
        $descuentoPorcentaje = $importes['descuento_porcentaje'];
        $descuentoImporte = $importes['descuento_importe'];
        $importe = $importes['importe'];

        $tipoRetencion = null;
        $retencionPorcentaje = 0;
        $retencionImporte = 0;

        if ($request->filled('tipo_retencion_id')) {
            $tipoRetencion = TipoRetencion::query()
                ->where('activo', true)
                ->findOrFail($request->integer('tipo_retencion_id'));

            $retencionPorcentaje = (float) $tipoRetencion->porcentaje;

            $retencionImporte = round(
                $importe * ($retencionPorcentaje / 100),
                2
            );
        }

        $civilConcept = null;
        $obraCivilInsumo = $this->resolveObraCivilInsumo($request, $oc);

        if (!$obraCivilInsumo && $request->filled('civil_concept_id')) {
            $civilConcept = \App\Models\CivilConcept::query()
                ->with('partida.building.catalogImport')
                ->where('id', $request->integer('civil_concept_id'))
                ->where('is_active', true)
                ->whereHas('partida.building.catalogImport', function ($query) use ($oc) {
                    $query->where('obra_id', $oc->obra_id)
                        ->whereIn('status', ['imported', 'validated']);
                })
                ->firstOrFail();
        }

        $detalle = new OrdenCompraDetalle();
        $detalle->orden_compra_id = $oc->id;
        $detalle->producto_id = ($civilConcept || $obraCivilInsumo) ? null : $request->producto_id;
        $detalle->civil_concept_id = $obraCivilInsumo ? null : $civilConcept?->id;
        $detalle->obra_civil_insumo_id = $obraCivilInsumo?->id;
        $detalle->legacy_prod_id = ($civilConcept || $obraCivilInsumo) ? null : $request->legacy_prod_id;
        $detalle->descripcion = $request->descripcion;
        $detalle->unidad = $request->unidad;
        $detalle->cantidad = $cantidad;
        $detalle->precio_unitario = $precio;
        $detalle->descuento_porcentaje = $descuentoPorcentaje;
        $detalle->descuento_importe = $descuentoImporte;
        $detalle->importe = $importe;
        $detalle->iva = $request->filled('iva')
            ? (float) $request->iva
            : 0;
        $detalle->iva_importe_manual = $request->filled('iva_importe_manual')
            ? (float) $request->iva_importe_manual
            : null;

        $detalle->tipo_retencion_id = $tipoRetencion?->id;
        $detalle->retencion_porcentaje = $retencionPorcentaje;
        $detalle->retenciones = $retencionImporte;

        $detalle->otros_impuestos = $request->filled('otros_impuestos')
            ? (float) $request->otros_impuestos
            : 0;

        if (is_null($detalle->tipo_cambio)) {
            $detalle->tipo_cambio = $oc->tipo_cambio;
        }

        $detalle->notas = $request->notas;

        if ($civilConcept) {
            $detalle->obra_civil_insumo_snapshot = null;
            $partida = $civilConcept->partida;
            $building = $partida?->building;
            $import = $building?->catalogImport;

            $detalle->civil_concept_snapshot = [
                'civil_catalog_import_id' => $import?->id,
                'filename' => $import?->filename,
                'building' => $building?->name,
                'partida_id' => $partida?->id,
                'partida_code' => $partida?->code,
                'partida_name' => $partida?->name,
                'excel_code' => $civilConcept->excel_code,
                'description' => $civilConcept->description,
                'unit' => $civilConcept->unit,
                'budget_quantity' => (float) $civilConcept->budget_quantity,
                'unit_price' => (float) $civilConcept->unit_price,
                'budget_amount' => (float) $civilConcept->budget_amount,
            ];
        }

        if ($obraCivilInsumo) {
            $detalle->civil_concept_snapshot = null;
            $detalle->obra_civil_insumo_snapshot = $this->buildObraCivilInsumoSnapshot($obraCivilInsumo);
        }

        $detalle->save();

        logger()->info('OC Detalle guardado', [
            'oc_id' => $oc->id,
            'detalle_id' => $detalle->id,
            'proveedor_id' => $oc->proveedor_id,
            'producto_id' => $detalle->producto_id,
            'civil_concept_id' => $detalle->civil_concept_id,
            'obra_civil_insumo_id' => $detalle->obra_civil_insumo_id,
            'legacy_prod_id' => $detalle->legacy_prod_id,
            'descripcion' => $detalle->descripcion,
            'precio_unitario' => $detalle->precio_unitario,
            'tipo_retencion_id' => $detalle->tipo_retencion_id,
            'retencion_porcentaje' => $detalle->retencion_porcentaje,
            'retenciones' => $detalle->retenciones,
        ]);

        $this->syncProductoProveedorDesdeDetalle($oc, $detalle);

        OrdenCompraTotalesService::recalcular($oc);

        return back()->with(
            'success',
            'Detalle agregado y totales recalculados.'
        );
    });
}


    private function resolveObraCivilInsumo(Request $request, OrdenCompra $oc): ?ObraCivilInsumo
    {
        if (!$request->filled('obra_civil_insumo_id')) {
            return null;
        }

        return ObraCivilInsumo::query()
            ->with('import')
            ->where('id', $request->integer('obra_civil_insumo_id'))
            ->where('obra_id', $oc->obra_id)
            ->where('is_active', true)
            ->where('tipo', 'material')
            ->firstOrFail();
    }

    private function buildObraCivilInsumoSnapshot(ObraCivilInsumo $insumo): array
    {
        $import = $insumo->import;

        return [
            'obra_civil_insumo_import_id' => $import?->id,
            'filename' => $import?->filename,
            'sheet_name' => $import?->sheet_name,
            'source_row' => $insumo->source_row,
            'tipo' => $insumo->tipo,
            'codigo' => $insumo->codigo,
            'concepto' => $insumo->concepto,
            'unidad' => $insumo->unidad,
            'cantidad_presupuestada' => (float) $insumo->cantidad_presupuestada,
            'precio_unitario' => (float) $insumo->precio_unitario,
            'importe_importado' => (float) $insumo->importe_importado,
            'importe_calculado' => (float) $insumo->importe_calculado,
        ];
    }
    private function calcularImportesPartida(Request $request, float $cantidad, float $precio): array
    {
        $bruto = round($cantidad * $precio, 2);
        $descuentoPorcentaje = $request->filled('descuento_porcentaje')
            ? (float) $request->descuento_porcentaje
            : 0.0;

        $descuentoPorcentaje = min(100, max(0, $descuentoPorcentaje));
        $descuentoImporte = round($bruto * ($descuentoPorcentaje / 100), 2);

        $importe = $request->filled('importe')
            ? (float) $request->importe
            : round($bruto - $descuentoImporte, 2);

        return [
            'bruto' => $bruto,
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_importe' => $descuentoImporte,
            'importe' => max(0, $importe),
        ];
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

        if (in_array($oc->estado_normalizado, ['autorizada','verificada','cancelada'], true)) {
            return back()->with('error', 'No puedes modificar detalles en una orden autorizada, verificada o cancelada.');
        }

        $detalle = OrdenCompraDetalle::where('orden_compra_id', $oc->id)->findOrFail($detalleId);

        return DB::transaction(function () use ($request, $oc, $detalle) {

            $cantidad = (float)$request->cantidad;
            $precio   = (float)$request->precio_unitario;
            $importes = $this->calcularImportesPartida($request, $cantidad, $precio);
            $descuentoPorcentaje = $importes['descuento_porcentaje'];
            $descuentoImporte = $importes['descuento_importe'];
            $importe = $importes['importe'];

            $obraCivilInsumo = $this->resolveObraCivilInsumo($request, $oc);

            $detalle->producto_id     = $obraCivilInsumo ? null : $request->producto_id;
            $detalle->civil_concept_id = $obraCivilInsumo ? null : $request->civil_concept_id;
            $detalle->obra_civil_insumo_id = $obraCivilInsumo?->id;
            $detalle->legacy_prod_id  = $obraCivilInsumo ? null : $request->legacy_prod_id;
            if ($obraCivilInsumo) {
                $snapshotPrecioTope = $detalle->obra_civil_insumo_snapshot['precio_unitario'] ?? null;
                $detalle->precio_tope = $detalle->precio_tope
                    ?? (is_numeric($snapshotPrecioTope) ? (float) $snapshotPrecioTope : (float) $obraCivilInsumo->precio_unitario);
                $detalle->civil_concept_snapshot = null;
                $detalle->obra_civil_insumo_snapshot = $this->buildObraCivilInsumoSnapshot($obraCivilInsumo);
            } else {
                $detalle->obra_civil_insumo_snapshot = null;
            }

            $detalle->descripcion     = $request->descripcion;
            $detalle->unidad          = $request->unidad;

            $detalle->cantidad        = $cantidad;
            $detalle->precio_unitario = $precio;
            $detalle->descuento_porcentaje = $descuentoPorcentaje;
            $detalle->descuento_importe = $descuentoImporte;

            $detalle->importe         = $importe;
            $detalle->iva             = $request->filled('iva') ? (float)$request->iva : 0;
            $detalle->iva_importe_manual = $request->filled('iva_importe_manual') ? (float) $request->iva_importe_manual : null;

            $tipoRetencion = null;
            $retencionPorcentaje = 0;
            $retencionImporte = 0;

            if ($request->filled('tipo_retencion_id')) {
                $tipoRetencion = TipoRetencion::query()
                    ->where('activo', true)
                    ->findOrFail($request->integer('tipo_retencion_id'));

                $retencionPorcentaje = (float) $tipoRetencion->porcentaje;
                $retencionImporte = round($importe * ($retencionPorcentaje / 100), 2);
            }

            $detalle->tipo_retencion_id = $tipoRetencion?->id;
            $detalle->retencion_porcentaje = $retencionPorcentaje;
            $detalle->retenciones     = $retencionImporte;
            $detalle->otros_impuestos = $request->filled('otros_impuestos') ? (float)$request->otros_impuestos : 0;

            $detalle->notas           = $request->notas;

            // si quieres forzar que siempre siga el tipo_cambio del encabezado:
            $detalle->tipo_cambio = $oc->tipo_cambio;

            $detalle->save();

            OrdenCompraTotalesService::recalcular($oc);

            if ($request->expectsJson()) {
                return response()->json(['ok' => true]);
            }

            return back()->with('success', 'Detalle actualizado y totales recalculados.');
        });
    }

    public function destroy(Request $request, $ordenCompraId, $detalleId)
    {
        $oc = OrdenCompra::findOrFail($ordenCompraId);

        if (in_array($oc->estado_normalizado, ['autorizada','verificada','cancelada'], true)) {
            return back()->with('error', 'No puedes modificar detalles en una orden autorizada, verificada o cancelada.');
        }

        $detalle = OrdenCompraDetalle::where('orden_compra_id', $oc->id)->findOrFail($detalleId);

        return DB::transaction(function () use ($oc, $detalle) {
            $detalle->delete();
            OrdenCompraTotalesService::recalcular($oc);
            return back()->with('success', 'Detalle eliminado y totales recalculados.');
        });
    }
}
