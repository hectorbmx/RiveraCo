<?php

namespace App\Services\Obras;

use App\Models\InventarioDocumento;
use App\Models\Obra;
use App\Models\ObraDevolucion;
use App\Models\ObraDevolucionRecepcion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObraDevolucionAplicacionService
{
    public function aplicar(ObraDevolucionRecepcion $recepcion, User $usuario): ObraDevolucionRecepcion
    {
        if (! $usuario->can('obra.devoluciones.view.access') || ! $usuario->can('obra.devoluciones.receive.access')) throw new AuthorizationException();
        return DB::transaction(function () use ($recepcion, $usuario) {
            $solicitudId = $recepcion->devolucion_id;
            $obraId = ObraDevolucion::whereKey($solicitudId)->value('obra_id');
            Obra::whereKey($obraId)->lockForUpdate()->firstOrFail();
            $solicitud = ObraDevolucion::whereKey($solicitudId)->lockForUpdate()->firstOrFail();
            $recepcion = ObraDevolucionRecepcion::whereKey($recepcion->id)->where('devolucion_id', $solicitud->id)->lockForUpdate()->firstOrFail();
            if ($recepcion->estado === 'aplicada') return $recepcion;
            if (! \App\Models\Almacen::whereKey($recepcion->almacen_id)->where('activo', true)->lockForUpdate()->first()) throw ValidationException::withMessages(['recepcion' => 'El almacén receptor ya no está activo.']);
            $recepcion->detalles()->orderBy('id')->lockForUpdate()->get();
            $solicitud->detalles()->orderBy('id')->lockForUpdate()->get();
            $preparado = app(ObraDevolucionEntradaPreparacionService::class)->preparar($recepcion);
            if (! $preparado['entradas']) {
                if ($preparado['ya_aplicadas']) return $recepcion;
                throw ValidationException::withMessages(['recepcion' => 'No hay partidas en buen estado listas para aplicar.']);
            }
            $entregas = app(MaterialEntregadoConsultaService::class)->porObra($obraId)->keyBy(fn ($e) => $e->origen_tipo.':'.$e->origen_id);
            $detalles = $solicitud->detalles()->get()->keyBy('id');
            $lineas = collect($preparado['entradas'])->sortBy(fn ($p) => str_pad((string) $p['producto_id'], 20, '0', STR_PAD_LEFT).':'.str_pad((string) $p['recepcion_detalle_id'], 20, '0', STR_PAD_LEFT));
            $documento = InventarioDocumento::create(['tipo' => 'devolucion', 'almacen_id' => $recepcion->almacen_id, 'obra_id' => $obraId,
                'estado' => 'aplicado', 'fecha' => $recepcion->fecha_recepcion, 'creado_por' => $usuario->id,
                'motivo' => 'Devolución de obra', 'notas' => 'Recepción '.$recepcion->folio.' · solicitud '.$solicitud->folio]);
            $documento->update(['folio' => 'DEV-INV-'.str_pad((string) $documento->id, 8, '0', STR_PAD_LEFT)]);
            foreach ($lineas as $linea) {
                $partida = $detalles->get($linea['solicitud_detalle_id']);
                $tipo = $partida->recepcion_directa_detalle_id ? 'recepcion_directa' : ($partida->inventario_movimiento_id ? 'salida_general' : 'salida_huentitan');
                $campo = ['recepcion_directa' => 'recepcion_directa_detalle_id', 'salida_general' => 'inventario_movimiento_id', 'salida_huentitan' => 'huentitan_salida_detalle_id'][$tipo];
                $entrega = $entregas->get($tipo.':'.$partida->$campo);
                if (! $entrega || (int) $entrega->producto_id !== (int) $partida->producto_id || abs((float) $entrega->cantidad - (float) $partida->cantidad_entregada_snapshot) > 0.000001 || abs((float) $entrega->importe_mxn - (float) $partida->importe_origen_mxn) > 0.000001 || abs((float) $entrega->costo_unitario_mxn - (float) $partida->costo_unitario_mxn) > 0.00000001) throw ValidationException::withMessages(['recepcion' => 'La entrega original o su costo cambió; requiere revisión.']);
                $anteriores = DB::table('obra_devolucion_recepcion_detalles as rd')->join('obra_devolucion_recepciones as r', 'r.id', '=', 'rd.recepcion_id')->join('obra_devolucion_detalles as p', 'p.id', '=', 'rd.devolucion_detalle_id')->where('p.'.$campo, $partida->$campo)
                    ->where(fn ($q) => $q->whereIn('r.estado', ['aplicada', 'aplicado'])->orWhere(fn ($p) => $p->where('r.estado', 'pendiente_aplicacion')->whereNotNull('rd.inventario_movimiento_id')->whereNotNull('rd.inventario_documento_detalle_id')->whereNotNull('rd.importe_mxn')))
                    ->selectRaw('COALESCE(SUM(rd.cantidad_recibida), 0) as cantidad, COALESCE(SUM(rd.importe_mxn), 0) as importe')->first();
                $cantidad = (float) $linea['cantidad']; $anterior = (float) $anteriores->cantidad; $total = (float) $entrega->cantidad; $base = (float) $entrega->importe_mxn;
                if ($total <= 0 || $anterior + $cantidad > $total + 0.000001 || abs((float) $anteriores->importe - round($base * $anterior / $total, 2)) > 0.001) throw ValidationException::withMessages(['recepcion' => 'El saldo o costo devuelto de la entrega es inconsistente.']);
                $importe = round($base * ($anterior + $cantidad) / $total, 2) - (float) $anteriores->importe;
                DB::table('inventario_stock')->insertOrIgnore(['almacen_id' => $recepcion->almacen_id, 'producto_id' => $partida->producto_id, 'stock_actual' => 0, 'stock_reservado' => 0, 'valor_total' => 0, 'costo_promedio' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $stock = DB::table('inventario_stock')->where('almacen_id', $recepcion->almacen_id)->where('producto_id', $partida->producto_id)->lockForUpdate()->first();
                $nuevoStock = (float) $stock->stock_actual + $cantidad; $nuevoValor = (float) $stock->valor_total + $importe;
                DB::table('inventario_stock')->where('id', $stock->id)->update(['stock_actual' => $nuevoStock, 'valor_total' => $nuevoValor, 'costo_promedio' => $nuevoStock > 0 ? $nuevoValor / $nuevoStock : 0, 'updated_at' => now()]);
                $docDetalle = $documento->detalles()->create(['producto_id' => $partida->producto_id, 'cantidad' => $cantidad, 'costo_unitario' => round((float) $partida->costo_unitario_mxn, 4), 'notas' => $recepcion->folio]);
                $directa = $tipo === 'recepcion_directa' ? $partida->recepcionDirectaDetalle : null;
                $mov = DB::table('inventario_movimientos')->insertGetId(['almacen_id' => $recepcion->almacen_id, 'producto_id' => $partida->producto_id, 'documento_id' => $documento->id,
                    'compra_recepcion_directa_id' => $directa?->recepcion_id, 'compra_recepcion_directa_detalle_id' => $directa?->id,
                    'fecha' => $recepcion->fecha_recepcion, 'tipo_movimiento' => 'in', 'cantidad' => $cantidad, 'costo_unitario' => round((float) $partida->costo_unitario_mxn, 4),
                    'importe_costo' => $importe, 'saldo_cantidad' => $nuevoStock, 'obra_id' => $obraId, 'creado_por' => $usuario->id, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('obra_devolucion_recepcion_detalles')->where('id', $linea['recepcion_detalle_id'])->update(['inventario_documento_detalle_id' => $docDetalle->id, 'inventario_movimiento_id' => $mov, 'importe_mxn' => $importe, 'updated_at' => now()]);
            }
            if (! $preparado['pendientes']) $recepcion->update(['estado' => 'aplicada']);
            $consulta = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $solicitud->id)->firstOrFail();
            if ($consulta->detalles->contains(fn ($p) => $p->consulta_pendiente === null)) throw ValidationException::withMessages(['recepcion' => 'El saldo de la solicitud es inconsistente.']);
            if ($consulta->detalles->every(fn ($p) => $p->consulta_pendiente == 0) && ! $solicitud->recepciones()->where('estado', 'pendiente_aplicacion')->exists()) $solicitud->update(['estado' => 'recibida']);
            return $recepcion;
        }, 3);
    }
}
