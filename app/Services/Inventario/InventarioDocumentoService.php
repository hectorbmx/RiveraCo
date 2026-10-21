<?php

namespace App\Services\Inventario;

use App\Models\InventarioDocumento;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\MantenimientoAbastecimiento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventarioDocumentoService
{
    public function aplicar(InventarioDocumento $doc): void
{
    DB::transaction(function () use ($doc) {
        $doc->refresh();

        if ($doc->estado !== 'borrador') {
            throw new RuntimeException('El documento no está en borrador.');
        }

        $doc->load('detalles');

        if ($doc->detalles->isEmpty()) {
            throw new RuntimeException('El documento no tiene detalles.');
        }

        // Bloqueamos las OCs relacionadas para evitar dos recepciones simultáneas.
        $ordenCompraIds = $doc->detalles
            ->filter(fn ($detalle) => $doc->tipo === 'entrada' && $detalle->orden_compra_detalle_id)
            ->map(fn ($detalle) => (int) ($detalle->orden_compra_id ?: $doc->orden_compra_id))
            ->filter()
            ->when($doc->orden_compra_id, fn ($ids) => $ids->push((int) $doc->orden_compra_id))
            ->unique()
            ->sort()
            ->values();

        $ordenesCompra = collect();
        foreach ($ordenCompraIds as $ordenCompraId) {
            $ordenesCompra->put(
                $ordenCompraId,
                OrdenCompra::query()->whereKey($ordenCompraId)->lockForUpdate()->firstOrFail()
            );
        }

        [$esEntrada, $esSalida, $tipoMovimiento] = $this->resolveDireccion($doc);

        foreach ($doc->detalles as $det) {
            $cantidad = (float) $det->cantidad;

            if ($cantidad <= 0) {
                throw new RuntimeException("Cantidad inválida en detalle {$det->id}");
            }

            $ocDetalle = null;
            if ($doc->tipo === 'entrada' && $det->orden_compra_detalle_id) {
                $ocDetalle = OrdenCompraDetalle::query()
                    ->whereKey($det->orden_compra_detalle_id)
                    ->lockForUpdate()
                    ->first();

                if (!$ocDetalle) {
                    throw new RuntimeException("El detalle {$det->id} no tiene una partida válida de orden de compra.");
                }

                $ordenCompraId = (int) ($det->orden_compra_id ?: $doc->orden_compra_id);
                if (!$ordenCompraId || (int) $ocDetalle->orden_compra_id !== $ordenCompraId) {
                    throw new RuntimeException("La partida {$det->id} no pertenece a la orden de compra seleccionada.");
                }

                $pendiente = max(0, (float) $ocDetalle->cantidad - (float) $ocDetalle->cantidad_recibida);
                if ($cantidad > $pendiente + 0.0005) {
                    $nombre = $det->producto?->nombre ?? $ocDetalle->descripcion ?? "partida {$det->id}";
                    throw new RuntimeException("La cantidad a recibir de '{$nombre}' ({$cantidad}) excede el saldo pendiente de la OC ({$pendiente}).");
                }
            }

            // Lock del stock.
            $stockRow = DB::table('inventario_stock')
                ->where('almacen_id', $doc->almacen_id)
                ->where('producto_id', $det->producto_id)
                ->lockForUpdate()
                ->first();

            if (!$stockRow) {
                DB::table('inventario_stock')->insert([
                    'almacen_id'      => $doc->almacen_id,
                    'producto_id'     => $det->producto_id,
                    'stock_actual'    => 0,
                    'stock_reservado' => 0,
                    'valor_total'     => 0,
                    'costo_promedio'  => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $actual = 0.0;
                $valorTotal = 0.0;
                $costoProm = 0.0;
            } else {
                $actual = (float) $stockRow->stock_actual;
                $valorTotal = (float) $stockRow->valor_total;
                $costoProm = (float) $stockRow->costo_promedio;
            }

            $costoUnitario = 0.0;
            if ($esEntrada) {
                if ($det->costo_unitario === null) {
                    if (in_array($doc->tipo, ['devolucion'], true)) {
                        $costoUnitario = $costoProm;
                    } else {
                        throw new RuntimeException("Falta costo_unitario en detalle {$det->id} para documento de entrada.");
                    }
                } else {
                    $costoUnitario = (float) $det->costo_unitario;
                }
            } else {
                $costoUnitario = $costoProm;
            }

            if ($esSalida) {
                $nuevoStock = $actual - $cantidad;
                if ($nuevoStock < 0) {
                    throw new RuntimeException("Stock insuficiente (producto_id={$det->producto_id}). Disponible: {$actual}, requiere: {$cantidad}");
                }

                $nuevoValor = max(0, $valorTotal - ($cantidad * $costoUnitario));
                $nuevoCostoProm = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;
            } else {
                $nuevoStock = $actual + $cantidad;
                $nuevoValor = $valorTotal + ($cantidad * $costoUnitario);
                $nuevoCostoProm = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;
            }

            DB::table('inventario_stock')
                ->where('almacen_id', $doc->almacen_id)
                ->where('producto_id', $det->producto_id)
                ->update([
                    'stock_actual' => $nuevoStock,
                    'valor_total' => $nuevoValor,
                    'costo_promedio' => $nuevoCostoProm,
                    'updated_at' => now(),
                ]);

            DB::table('inventario_movimientos')->insert([
                'almacen_id' => $doc->almacen_id,
                'producto_id' => $det->producto_id,
                'documento_id' => $doc->id,
                'fecha' => $doc->fecha,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'saldo_cantidad' => $nuevoStock,
                'obra_id' => $doc->obra_id,
                'residente_id' => $doc->residente_id,
                'creado_por' => $doc->creado_por,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($ocDetalle) {
                $ocDetalle->cantidad_recibida = (float) $ocDetalle->cantidad_recibida + $cantidad;
                $ocDetalle->save();
            }
        }

        foreach ($ordenesCompra as $ordenCompra) {
            $this->actualizarEstadoRecepcionOrdenCompra($ordenCompra);
        }

        if ($doc->tipo === 'entrada') {
            $this->actualizarSolicitudesCompraPorEntrada($doc);
        }

        if ($doc->tipo === 'salida' && $doc->mantenimiento_abastecimiento_id) {
            $this->actualizarSolicitudAlmacen($doc);
        }

        $doc->estado = 'aplicado';
        $doc->save();
    });
}

private function actualizarSolicitudAlmacen(InventarioDocumento $doc): void
{
    $solicitud = MantenimientoAbastecimiento::query()
        ->with('mantenimiento')
        ->whereKey($doc->mantenimiento_abastecimiento_id)
        ->lockForUpdate()
        ->first();

    if (!$solicitud || $solicitud->tipo !== MantenimientoAbastecimiento::TIPO_ALMACEN) {
        throw new RuntimeException('La salida está vinculada a una solicitud de almacén inválida.');
    }

    $detallesSolicitud = $solicitud->detalles()
        ->lockForUpdate()
        ->get()
        ->keyBy('producto_id');

    foreach ($doc->detalles as $detalleSalida) {
        $detalleSolicitud = $detallesSolicitud->get($detalleSalida->producto_id);

        if (!$detalleSolicitud) {
            throw new RuntimeException('La partida de salida no pertenece a la solicitud de almacén.');
        }

        $detalleSolicitud->cantidad_atendida = min(
            (float) $detalleSolicitud->cantidad_solicitada,
            (float) $detalleSolicitud->cantidad_atendida + (float) $detalleSalida->cantidad
        );
        $detalleSolicitud->estado = (float) $detalleSolicitud->cantidad_atendida + 0.0005 >= (float) $detalleSolicitud->cantidad_solicitada
            ? 'surtida'
            : ($detalleSolicitud->cantidad_atendida > 0 ? 'parcial' : 'pendiente');
        $detalleSolicitud->save();
    }

    $solicitud->refresh();
    $todosSurtidos = $solicitud->detalles()->where('estado', '!=', 'surtida')->doesntExist();
    $algunSurtido = $solicitud->detalles()->where('cantidad_atendida', '>', 0)->exists();

    $solicitud->estado = $todosSurtidos ? 'surtida' : ($algunSurtido ? 'parcial' : 'pendiente');
    $solicitud->save();

    if ($solicitud->mantenimiento) {
        $solicitud->mantenimiento->update([
            'abastecimiento_estado' => $todosSurtidos ? 'surtida' : 'surtida_parcial',
        ]);
    }
}

private function actualizarSolicitudesCompraPorEntrada(InventarioDocumento $doc): void
{
    $detalleIds = $doc->detalles->pluck('orden_compra_detalle_id')->filter()->unique()->values();
    if ($detalleIds->isEmpty()) {
        return;
    }

    $solicitudes = MantenimientoAbastecimiento::query()
        ->with('mantenimiento')
        ->where('tipo', MantenimientoAbastecimiento::TIPO_COMPRA)
        ->whereHas('detalles', fn ($query) => $query->whereIn('id', function ($subquery) use ($detalleIds) {
            $subquery->select('mantenimiento_abastecimiento_detalle_id')
                ->from('orden_compra_detalles')
                ->whereIn('id', $detalleIds->all())
                ->whereNotNull('mantenimiento_abastecimiento_detalle_id');
        }))
        ->lockForUpdate()
        ->get();

    foreach ($solicitudes as $solicitud) {
        $ocDetalleIds = $solicitud->detalles()
            ->pluck('id')
            ->all();

        $totales = DB::table('orden_compra_detalles')
            ->whereIn('mantenimiento_abastecimiento_detalle_id', $ocDetalleIds)
            ->selectRaw('COALESCE(SUM(cantidad), 0) as solicitada, COALESCE(SUM(cantidad_recibida), 0) as recibida')
            ->first();

        $solicitada = (float) ($totales->solicitada ?? 0);
        $recibida = (float) ($totales->recibida ?? 0);
        if ($recibida <= 0) {
            continue;
        }

        $solicitud->estado = $recibida + 0.0005 >= $solicitada
            ? 'material_en_almacen'
            : 'recibida_parcial';
        $solicitud->save();

        if ($solicitud->mantenimiento) {
            $solicitud->mantenimiento->update([
                'abastecimiento_estado' => $solicitud->estado,
            ]);
        }
    }
}
// public function cancelar(InventarioDocumento $doc): void
// {
//     DB::transaction(function () use ($doc) {

//         $doc->refresh();

//         if ($doc->estado !== 'aplicado') {
//             throw new RuntimeException("Solo se pueden cancelar documentos aplicados.");
//         }

//         if ($doc->tipo === 'cancelacion') {
//             throw new RuntimeException("No se puede cancelar un documento de cancelación.");
//         }

//         // Cargar movimientos del documento original
//         $movs = DB::table('inventario_movimientos')
//             ->where('documento_id', $doc->id)
//             ->orderByDesc('id') // inverso por seguridad
//             ->get();

//         if ($movs->isEmpty()) {
//             throw new RuntimeException("El documento no tiene movimientos para cancelar.");
//         }

//         // Crear documento de cancelación
//         $docCancel = InventarioDocumento::create([
//             'tipo'                => 'cancelacion',
//             'almacen_id'          => $doc->almacen_id,
//             'obra_id'             => $doc->obra_id,
//             'estado'              => 'borrador',
//             'fecha'               => now(),
//             'motivo'              => 'cancelación de documento '.$doc->id,
//             'creado_por'          => $doc->creado_por,
//             'residente_id'        => $doc->residente_id,
//             'documento_origen_id' => $doc->id,
//         ]);

//         // Revertir cada movimiento
//         foreach ($movs as $m) {

//             // Lock del stock
//             $stockRow = DB::table('inventario_stock')
//                 ->where('almacen_id', $m->almacen_id)
//                 ->where('producto_id', $m->producto_id)
//                 ->lockForUpdate()
//                 ->first();

//             if (!$stockRow) {
//                 throw new RuntimeException("Stock inexistente para revertir (producto_id={$m->producto_id}).");
//             }

//             $actual     = (float) $stockRow->stock_actual;
//             $valorTotal = (float) $stockRow->valor_total;
//             $costoProm  = (float) $stockRow->costo_promedio;

//             $cantidad = (float) $m->cantidad;
//             $costo    = (float) $m->costo_unitario;

//             // Invertir movimiento
//             if ($m->tipo_movimiento === 'in') {
//                 // original entró → ahora sale
//                 $nuevoStock = $actual - $cantidad;
//                 if ($nuevoStock < 0) {
//                     throw new RuntimeException(
//                         "Stock insuficiente al cancelar (producto_id={$m->producto_id})."
//                     );
//                 }
//                 $nuevoValor = $valorTotal - ($cantidad * $costo);
//             } else {
//                 // original salió → ahora entra
//                 $nuevoStock = $actual + $cantidad;
//                 $nuevoValor = $valorTotal + ($cantidad * $costo);
//             }

//             $nuevoCostoProm = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;

//             // Actualizar stock
//             DB::table('inventario_stock')
//                 ->where('almacen_id', $m->almacen_id)
//                 ->where('producto_id', $m->producto_id)
//                 ->update([
//                     'stock_actual'   => $nuevoStock,
//                     'valor_total'    => $nuevoValor,
//                     'costo_promedio' => $nuevoCostoProm,
//                     'updated_at'     => now(),
//                 ]);

//             // Insertar movimiento inverso
//             DB::table('inventario_movimientos')->insert([
//                 'almacen_id'      => $m->almacen_id,
//                 'producto_id'     => $m->producto_id,
//                 'documento_id'    => $docCancel->id,
//                 'fecha'           => now(),
//                 'tipo_movimiento' => $m->tipo_movimiento === 'in' ? 'out' : 'in',
//                 'cantidad'        => $cantidad,
//                 'costo_unitario'  => $costo,
//                 'saldo_cantidad'  => $nuevoStock,
//                 'obra_id'         => $m->obra_id,
//                 'residente_id'    => $m->residente_id,
//                 'creado_por'      => $doc->creado_por,
//                 'created_at'      => now(),
//                 'updated_at'      => now(),
//             ]);
//         }

//         $docCancel->estado = 'aplicado';
//         $docCancel->save();

//         $doc->estado = 'cancelado';
//         $doc->save();
//     });
// }
public function cancelar(InventarioDocumento $doc): void
{
    DB::transaction(function () use ($doc) {

        $doc->refresh();

        if ($doc->estado !== 'aplicado') {
            throw new RuntimeException("Solo se pueden cancelar documentos aplicados.");
        }

        if ($doc->tipo === 'cancelacion') {
            throw new RuntimeException("No se puede cancelar un documento de cancelación.");
        }

        // ✅ Cargar detalles del documento original (para copiarlos al doc de cancelación)
        $doc->load('detalles');

        // Cargar movimientos del documento original
        $movs = DB::table('inventario_movimientos')
            ->where('documento_id', $doc->id)
            ->orderByDesc('id') // inverso por seguridad
            ->get();

        if ($movs->isEmpty()) {
            throw new RuntimeException("El documento no tiene movimientos para cancelar.");
        }

        // Crear documento de cancelación
        $docCancel = InventarioDocumento::create([
            'tipo'                => 'cancelacion',
            'almacen_id'          => $doc->almacen_id,
            'obra_id'             => $doc->obra_id,
            'estado'              => 'borrador',
            'fecha'               => now(),
            'motivo'              => 'cancelación de documento '.$doc->id,
            'creado_por'          => $doc->creado_por,
            'residente_id'        => $doc->residente_id,
            'documento_origen_id' => $doc->id,
        ]);

        /**
         * ✅ Crear partidas (detalles) para el documento de cancelación.
         * - Preferimos copiar los detalles del documento original (lo más “auditable”).
         * - Si por alguna razón no hay detalles, derivamos desde movimientos agrupados.
         */
        if ($doc->detalles && $doc->detalles->count() > 0) {

            foreach ($doc->detalles as $d) {
                // Si tu modelo Detalle usa fillable, esto funciona.
                // Si no, usa DB::table('inventario_documento_detalles')->insert(...)
                $docCancel->detalles()->create([
                    'producto_id'                  => $d->producto_id,
                    'vehiculo_id'                  => $d->vehiculo_id,
                    'maquina_id'                   => $d->maquina_id,
                    'equipo_tipo'                  => $d->equipo_tipo,
                    'equipo_nombre_snapshot'      => $d->equipo_nombre_snapshot,
                    'equipo_identificador_snapshot'=> $d->equipo_identificador_snapshot,
                    'cantidad'                     => (float) $d->cantidad,
                    'costo_unitario'               => (float) $d->costo_unitario,
                    'notas'                        => $d->notas ?: ('Reversa de documento '.$doc->id),
                ]);
            }

        } else {

            // Fallback: crear detalles desde movimientos (agrupados por producto/costo)
            $agrupados = $movs
                ->groupBy(fn($m) => $m->producto_id.'|'.$m->costo_unitario)
                ->map(function ($items) {
                    $first = $items->first();
                    $sumCantidad = $items->sum(fn($x) => (float) $x->cantidad);

                    return [
                        'producto_id'    => (int) $first->producto_id,
                        'cantidad'       => (float) $sumCantidad,
                        'costo_unitario' => (float) $first->costo_unitario,
                    ];
                })
                ->values();

            foreach ($agrupados as $row) {
                $docCancel->detalles()->create([
                    'producto_id'    => $row['producto_id'],
                    'cantidad'       => $row['cantidad'],
                    'costo_unitario' => $row['costo_unitario'],
                    'notas'          => 'Partida generada desde movimientos (cancelación de '.$doc->id.')',
                ]);
            }
        }

        // Revertir cada movimiento
        foreach ($movs as $m) {

            // Lock del stock
            $stockRow = DB::table('inventario_stock')
                ->where('almacen_id', $m->almacen_id)
                ->where('producto_id', $m->producto_id)
                ->lockForUpdate()
                ->first();

            if (!$stockRow) {
                throw new RuntimeException("Stock inexistente para revertir (producto_id={$m->producto_id}).");
            }

            $actual     = (float) $stockRow->stock_actual;
            $valorTotal = (float) $stockRow->valor_total;

            $cantidad = (float) $m->cantidad;
            $costo    = (float) $m->costo_unitario;

            // Invertir movimiento
            if ($m->tipo_movimiento === 'in') {
                // original entró → ahora sale
                $nuevoStock = $actual - $cantidad;
                if ($nuevoStock < 0) {
                    throw new RuntimeException(
                        "Stock insuficiente al cancelar (producto_id={$m->producto_id})."
                    );
                }
                $nuevoValor = $valorTotal - ($cantidad * $costo);
            } else {
                // original salió → ahora entra
                $nuevoStock = $actual + $cantidad;
                $nuevoValor = $valorTotal + ($cantidad * $costo);
            }

            $nuevoCostoProm = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;

            // Actualizar stock
            DB::table('inventario_stock')
                ->where('almacen_id', $m->almacen_id)
                ->where('producto_id', $m->producto_id)
                ->update([
                    'stock_actual'   => $nuevoStock,
                    'valor_total'    => $nuevoValor,
                    'costo_promedio' => $nuevoCostoProm,
                    'updated_at'     => now(),
                ]);

            // Insertar movimiento inverso
            DB::table('inventario_movimientos')->insert([
                'almacen_id'      => $m->almacen_id,
                'producto_id'     => $m->producto_id,
                'documento_id'    => $docCancel->id,
                'fecha'           => now(),
                'tipo_movimiento' => $m->tipo_movimiento === 'in' ? 'out' : 'in',
                'cantidad'        => $cantidad,
                'costo_unitario'  => $costo,
                'saldo_cantidad'  => $nuevoStock,
                'obra_id'         => $m->obra_id,
                'residente_id'    => $m->residente_id,
                'creado_por'      => $doc->creado_por,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        if ($doc->tipo === 'salida' && $doc->mantenimiento_abastecimiento_id) {
            $this->revertirSolicitudAlmacen($doc);
        }

        if ($doc->tipo === 'entrada') {
            $ordenesCompraCanceladas = collect();

            foreach ($doc->detalles as $detalleCancelado) {
                if (!$detalleCancelado->orden_compra_detalle_id) {
                    continue;
                }

                $ocDetalle = OrdenCompraDetalle::query()
                    ->whereKey($detalleCancelado->orden_compra_detalle_id)
                    ->lockForUpdate()
                    ->first();

                if (!$ocDetalle) {
                    continue;
                }

                $ocDetalle->cantidad_recibida = max(
                    0,
                    (float) $ocDetalle->cantidad_recibida - (float) $detalleCancelado->cantidad
                );
                $ocDetalle->save();
                $ordenesCompraCanceladas->push((int) $ocDetalle->orden_compra_id);
            }

            foreach ($ordenesCompraCanceladas->unique() as $ordenCompraId) {
                $ordenCompra = OrdenCompra::query()
                    ->whereKey($ordenCompraId)
                    ->lockForUpdate()
                    ->first();

                if ($ordenCompra) {
                    $this->actualizarEstadoRecepcionOrdenCompra($ordenCompra);
                }
            }
        }
        // Marcar documentos
        $docCancel->estado = 'aplicado';
        $docCancel->save();

        $doc->estado = 'cancelado';
        $doc->save();
    });
}

private function revertirSolicitudAlmacen(InventarioDocumento $doc): void
{
    $solicitud = MantenimientoAbastecimiento::query()
        ->with('mantenimiento')
        ->whereKey($doc->mantenimiento_abastecimiento_id)
        ->lockForUpdate()
        ->first();

    if (!$solicitud || $solicitud->tipo !== MantenimientoAbastecimiento::TIPO_ALMACEN) {
        return;
    }

    $detallesSolicitud = $solicitud->detalles()
        ->lockForUpdate()
        ->get()
        ->keyBy('producto_id');

    foreach ($doc->detalles as $detalleSalida) {
        $detalleSolicitud = $detallesSolicitud->get($detalleSalida->producto_id);
        if (!$detalleSolicitud) {
            continue;
        }

        $detalleSolicitud->cantidad_atendida = max(
            0,
            (float) $detalleSolicitud->cantidad_atendida - (float) $detalleSalida->cantidad
        );
        $detalleSolicitud->estado = $detalleSolicitud->cantidad_atendida > 0 ? 'parcial' : 'pendiente';
        $detalleSolicitud->save();
    }

    $solicitud->refresh();
    $algunSurtido = $solicitud->detalles()->where('cantidad_atendida', '>', 0)->exists();
    $solicitud->estado = $algunSurtido ? 'parcial' : 'pendiente';
    $solicitud->save();

    if ($solicitud->mantenimiento) {
        $solicitud->mantenimiento->update([
            'abastecimiento_estado' => $algunSurtido ? 'surtida_parcial' : 'solicitud_almacen',
        ]);
    }
}

    private function actualizarEstadoRecepcionOrdenCompra(OrdenCompra $ordenCompra): void
    {
        $detalles = $ordenCompra->detalles()->lockForUpdate()->get();

        foreach ($detalles as $detalle) {
            $cantidadOrdenada = (float) ($detalle->cantidad ?? 0);
            $cantidadRecibida = (float) ($detalle->cantidad_recibida ?? 0);
            $estadoDetalle = $cantidadRecibida <= 0
                ? 'pendiente'
                : ($cantidadRecibida + 0.0005 >= $cantidadOrdenada ? 'recibida' : 'parcial');

            if ($detalle->estado_recepcion !== $estadoDetalle) {
                $detalle->forceFill(['estado_recepcion' => $estadoDetalle])->save();
            }
        }

        $cantidadOrdenada = (float) $detalles->sum(fn (OrdenCompraDetalle $detalle) => (float) $detalle->cantidad);
        $cantidadRecibida = (float) $detalles->sum(fn (OrdenCompraDetalle $detalle) => (float) $detalle->cantidad_recibida);

        $estado = $cantidadRecibida <= 0
            ? 'pendiente'
            : ($cantidadRecibida + 0.0005 >= $cantidadOrdenada ? 'recibida' : 'parcial');

        $ordenCompra->forceFill(['estado_recepcion' => $estado])->save();
    }
    private function isEntrada(string $tipo): bool
    {
        return in_array($tipo, ['inicial','entrada','devolucion','ajuste'], true);
    }

    private function isSalida(string $tipo): bool
    {
        return in_array($tipo, ['salida','resguardo'], true);
    }
    private function resolveDireccion(InventarioDocumento $doc): array
{
    // Regla normal
    if (in_array($doc->tipo, ['inicial','entrada','devolucion'], true)) {
        return [true, false, 'in']; // esEntrada, esSalida, tipoMovimiento
    }

    if (in_array($doc->tipo, ['salida','resguardo'], true)) {
        return [false, true, 'out'];
    }

    // Ajuste: decide por motivo
    if ($doc->tipo === 'ajuste') {
        $m = strtolower(trim((string) $doc->motivo));
        // aceptamos "in|out" o "entrada|salida"
        if (in_array($m, ['in','entrada'], true)) {
            return [true, false, 'in'];
        }
        if (in_array($m, ['out','salida'], true)) {
            return [false, true, 'out'];
        }
        throw new RuntimeException("Documento ajuste requiere motivo 'in/out' (o 'entrada/salida').");
    }

    throw new RuntimeException("Tipo de documento inválido: {$doc->tipo}");
}

}
