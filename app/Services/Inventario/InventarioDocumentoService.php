<?php

namespace App\Services\Inventario;

use App\Models\InventarioDocumento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventarioDocumentoService
{
    public function aplicar(InventarioDocumento $doc): void
{
    DB::transaction(function () use ($doc) {

        $doc->refresh();

        if ($doc->estado !== 'borrador') {
            throw new RuntimeException("El documento no está en borrador.");
        }

        $doc->load('detalles');

        if ($doc->detalles->isEmpty()) {
            throw new RuntimeException("El documento no tiene detalles.");
        }

        [$esEntrada, $esSalida, $tipoMovimiento] = $this->resolveDireccion($doc);

        foreach ($doc->detalles as $det) {

            $cantidad = (float) $det->cantidad;

            if ($cantidad <= 0) {
                throw new RuntimeException("Cantidad inválida en detalle {$det->id}");
            }

            // Lock del stock
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
                $costoProm  = 0.0;
            } else {
                $actual     = (float) $stockRow->stock_actual;
                $valorTotal = (float) $stockRow->valor_total;
                $costoProm  = (float) $stockRow->costo_promedio;
            }

            // -----------------------------
            // Determinar costo a usar
            // -----------------------------
            $costoUnitario = 0.0;

            if ($esEntrada) {
                // En entradas/inicial: costo_unitario debe venir
                // En devolucion/ajuste-in: puede venir, si no viene usamos costo_promedio actual
                if ($det->costo_unitario === null) {
                    // devolucion: si no viene costo, usa costoProm actual (práctico)
                    if (in_array($doc->tipo, ['devolucion'], true)) {
                        $costoUnitario = $costoProm;
                    } else {
                        throw new RuntimeException("Falta costo_unitario en detalle {$det->id} para documento de entrada.");
                    }
                } else {
                    $costoUnitario = (float) $det->costo_unitario;
                }
            } else {
                // Salida/Resguardo/Ajuste-out usa costo promedio del stock actual
                $costoUnitario = $costoProm;
            }

            // -----------------------------
            // Validar stock si es salida
            // -----------------------------
            if ($esSalida) {
                $nuevoStock = $actual - $cantidad;

                if ($nuevoStock < 0) {
                    throw new RuntimeException(
                        "Stock insuficiente (producto_id={$det->producto_id}). Disponible: {$actual}, requiere: {$cantidad}"
                    );
                }

                // Valor: sale a costo promedio
                $nuevoValor = $valorTotal - ($cantidad * $costoUnitario);
                if ($nuevoValor < 0) $nuevoValor = 0; // protección por redondeos

                $nuevoCostoProm = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;

            } else {
                // Entrada
                $nuevoStock = $actual + $cantidad;
                $nuevoValor = $valorTotal + ($cantidad * $costoUnitario);
                $nuevoCostoProm = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;
            }

            // -----------------------------
            // Actualizar inventario_stock
            // -----------------------------
            DB::table('inventario_stock')
                ->where('almacen_id', $doc->almacen_id)
                ->where('producto_id', $det->producto_id)
                ->update([
                    'stock_actual'   => $nuevoStock,
                    'valor_total'    => $nuevoValor,
                    'costo_promedio' => $nuevoCostoProm,
                    'updated_at'     => now(),
                ]);

            // -----------------------------
            // Insertar movimiento (Kardex)
            // -----------------------------
            DB::table('inventario_movimientos')->insert([
                'almacen_id'      => $doc->almacen_id,
                'producto_id'     => $det->producto_id,
                'documento_id'    => $doc->id,
                'fecha'           => $doc->fecha,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad'        => $cantidad,
                'costo_unitario'  => $costoUnitario,
                'saldo_cantidad'  => $nuevoStock,
                'obra_id'         => $doc->obra_id,
                'residente_id'    => $doc->residente_id,
                'creado_por'      => $doc->creado_por,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $doc->estado = 'aplicado';
        $doc->save();
    });
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

//         // Marcar documentos
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
                    'producto_id'    => $d->producto_id,
                    'cantidad'       => (float) $d->cantidad,
                    'costo_unitario' => (float) $d->costo_unitario,
                    'notas'          => $d->notas ?: ('Reversa de documento '.$doc->id),
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

        // Marcar documentos
        $docCancel->estado = 'aplicado';
        $docCancel->save();

        $doc->estado = 'cancelado';
        $doc->save();
    });
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
