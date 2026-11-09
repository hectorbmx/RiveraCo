<?php

namespace App\Services\Obras;

use App\Models\ObraDevolucionRecepcion;
use App\Models\ObraDevolucion;
use Illuminate\Validation\ValidationException;

/** Prepara partidas para la aplicación conjunta; no registra documentos ni movimientos. */
class ObraDevolucionEntradaPreparacionService
{
    public function preparar(ObraDevolucionRecepcion $recepcion, ?ObraDevolucion $solicitud = null): array
    {
        $recepcion->loadMissing('detalles.movimiento.documento', 'detalles.documentoDetalle.documento');
        if ($recepcion->estado !== 'pendiente_aplicacion' || $recepcion->detalles->isEmpty()) {
            throw ValidationException::withMessages(['recepcion' => 'La recepción no está pendiente de aplicación o no tiene partidas.']);
        }
        $solicitud ??= app(ObraDevolucionPendienteConsultaService::class)->consultar(null, (int) $recepcion->devolucion_id)->first();
        if (! $solicitud || (int) $solicitud->id !== (int) $recepcion->devolucion_id || ! in_array($solicitud->estado, ['solicitada', 'recepcion_parcial'], true)) throw ValidationException::withMessages(['recepcion' => 'La solicitud de origen no está disponible.']);
        $solicitadas = $solicitud->detalles->keyBy('id');
        $entradas = []; $pendientes = []; $aplicadas = [];
        foreach ($recepcion->detalles as $detalle) {
            $partida = $solicitadas->get($detalle->devolucion_detalle_id);
            if (! $partida || $partida->consulta_pendiente === null || (float) $detalle->cantidad_recibida <= 0
                || ObraDevolucionEstadoPartida::vinculosIncompletos($recepcion->estado, $detalle)
                || $detalle->costo_unitario_mxn === null || (float) $detalle->costo_unitario_mxn < 0
                || (string) $detalle->costo_unitario_mxn !== (string) $partida->costo_unitario_mxn) {
                throw ValidationException::withMessages(['recepcion' => 'Una partida tiene origen, cantidad, costo o movimiento inconsistente; requiere revisión.']);
            }
            if (ObraDevolucionEstadoPartida::aplicada($recepcion->estado, $detalle)) {
                $mov = $detalle->movimiento; $docDetalle = $detalle->documentoDetalle; $doc = $mov?->documento;
                if (! $mov || ! $docDetalle || ! $doc || $doc->estado !== 'aplicado' || $doc->tipo !== 'devolucion'
                    || (int) $docDetalle->documento_id !== (int) $mov->documento_id || (int) $docDetalle->producto_id !== (int) $partida->producto_id
                    || abs((float) $docDetalle->cantidad - (float) $detalle->cantidad_recibida) > 0.000001
                    || (int) $doc->obra_id !== (int) $solicitud->obra_id || (int) $doc->almacen_id !== (int) $recepcion->almacen_id || (int) $mov->obra_id !== (int) $solicitud->obra_id
                    || (int) $mov->almacen_id !== (int) $recepcion->almacen_id || (int) $mov->producto_id !== (int) $partida->producto_id
                    || $mov->tipo_movimiento !== 'in' || abs((float) $mov->cantidad - (float) $detalle->cantidad_recibida) > 0.000001
                    || $mov->importe_costo === null || abs((float) $mov->importe_costo - (float) $detalle->importe_mxn) > 0.001) throw ValidationException::withMessages(['recepcion' => 'Una partida aplicada tiene vínculos inconsistentes; requiere revisión.']);
                $aplicadas[] = $detalle->id;
                continue;
            }
            if ($detalle->condicion_recibida !== 'bueno' || $partida->condicion_declarada !== 'bueno') {
                $pendientes[] = ['recepcion_detalle_id' => $detalle->id, 'descripcion' => $partida->descripcion,
                    'motivo' => $detalle->condicion_recibida !== $partida->condicion_declarada ? 'Condición diferente; pendiente de aclaración.' : 'Material dañado o desgastado; pendiente de evaluación.'];
                continue;
            }
            $entradas[] = [
                'recepcion_detalle_id' => $detalle->id, 'solicitud_detalle_id' => $partida->id,
                'producto_id' => $partida->producto_id, 'descripcion' => $partida->descripcion,
                'unidad' => $partida->unidad, 'cantidad' => $detalle->cantidad_recibida,
                'costo_unitario_mxn' => $partida->costo_unitario_mxn,
                'recepcion_directa_detalle_id' => $partida->recepcion_directa_detalle_id,
                'inventario_movimiento_origen_id' => $partida->inventario_movimiento_id,
                'huentitan_salida_detalle_id' => $partida->huentitan_salida_detalle_id,
            ];
        }
        return ['recepcion_id' => $recepcion->id, 'almacen_id' => $recepcion->almacen_id,
            'obra_id' => $solicitud->obra_id, 'entradas' => $entradas, 'pendientes' => $pendientes, 'ya_aplicadas' => $aplicadas];
    }
}
