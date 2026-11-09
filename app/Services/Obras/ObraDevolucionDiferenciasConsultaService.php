<?php

namespace App\Services\Obras;

use Illuminate\Database\Eloquent\Collection;

/** Consulta informativa: no modifica cantidades, condiciones ni estados. */
class ObraDevolucionDiferenciasConsultaService
{
    public function anexar(Collection $solicitudes): void
    {
        $solicitudes->loadMissing(['detalles', 'recepciones.detalles', 'recepciones.almacen']);
        foreach ($solicitudes as $solicitud) {
            $porPartida = []; $erroresSolicitud = [];
            $ids = $solicitud->detalles->modelKeys();
            foreach ($solicitud->recepciones as $recepcion) {
                if (! in_array($recepcion->estado, ['pendiente_aplicacion', 'aplicada', 'aplicado'], true)) continue;
                foreach ($recepcion->detalles as $detalle) {
                    if (! in_array($detalle->devolucion_detalle_id, $ids)) {
                        $erroresSolicitud[] = 'Una recepción contiene una partida ajena a la solicitud.';
                        continue;
                    }
                    $porPartida[$detalle->devolucion_detalle_id][] = [$recepcion, $detalle];
                }
            }
            $pendientes = 0; $diferencias = 0; $revisiones = 0;
            foreach ($solicitud->detalles as $partida) {
                $recibido = 0; $aplicado = 0; $evaluacion = 0; $historial = []; $errores = [];
                $solicitado = (int) round((float) $partida->cantidad_solicitada * 1000);
                foreach ($porPartida[$partida->id] ?? [] as [$recepcion, $detalle]) {
                    $cantidad = (int) round((float) $detalle->cantidad_recibida * 1000);
                    if ($cantidad <= 0 || (int) $recepcion->devolucion_id !== (int) $solicitud->id) $errores[] = 'Cantidad o recepción inconsistente.';
                    if (ObraDevolucionEstadoPartida::vinculosIncompletos($recepcion->estado, $detalle)) $errores[] = 'Vínculos de aplicación incompletos.';
                    if (! in_array($detalle->condicion_recibida, ['bueno', 'danado', 'desgastado'], true)) $errores[] = 'Condición recibida no identificada.';
                    $recibido += $cantidad;
                    $aplicada = ObraDevolucionEstadoPartida::aplicada($recepcion->estado, $detalle);
                    if ($aplicada) $aplicado += $cantidad;
                    if ($detalle->estado_evaluacion === 'pendiente_evaluacion') $evaluacion += $cantidad;
                    $condicionConocida = in_array($partida->condicion_declarada, ['bueno', 'danado', 'desgastado'], true);
                    $diferente = $condicionConocida && in_array($detalle->condicion_recibida, ['bueno', 'danado', 'desgastado'], true) && $partida->condicion_declarada !== $detalle->condicion_recibida;
                    if ($diferente) $diferencias++;
                    $historial[] = ['folio' => $recepcion->folio ?: 'Recepción #'.$recepcion->id, 'fecha' => $recepcion->fecha_recepcion,
                        'almacen' => $recepcion->almacen?->nombre, 'cantidad' => $cantidad / 1000.0,
                        'condicion' => $detalle->condicion_recibida, 'diferente' => $diferente, 'sin_referencia' => ! $condicionConocida,
                        'aplicada' => $aplicada,
                        'evaluacion' => $detalle->estado_evaluacion === 'pendiente_evaluacion', 'observaciones' => $detalle->observaciones];
                }
                if ($solicitado <= 0 || $recibido > $solicitado || $aplicado > $recibido || $evaluacion > $recibido) $errores[] = 'Las cantidades requieren revisión.';
                if ($errores) $revisiones++;
                if (! $errores && $recibido < $solicitado) $pendientes++;
                $partida->setAttribute('resumen_devolucion', ['recibido' => $recibido / 1000.0,
                    'pendiente' => $errores ? null : ($solicitado - $recibido) / 1000.0,
                    'aplicado' => $aplicado / 1000.0, 'por_aplicar' => ($recibido - $aplicado) / 1000.0,
                    'evaluacion' => $evaluacion / 1000.0, 'historial' => $historial, 'errores' => array_values(array_unique($errores))]);
            }
            $solicitud->setAttribute('resumen_diferencias', ['partidas_pendientes' => $pendientes,
                'condiciones_diferentes' => $diferencias, 'partidas_revision' => $revisiones,
                'errores' => array_values(array_unique($erroresSolicitud))]);
        }
    }
}
