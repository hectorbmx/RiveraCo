<?php

namespace App\Services\Obras;

/** Los vínculos de movimiento son la evidencia de aplicación de una partida en recepción mixta. */
class ObraDevolucionEstadoPartida
{
    public static function aplicada(string $estado, $detalle): bool
    {
        if (in_array($estado, ['aplicada', 'aplicado'], true)) return true;
        return $estado === 'pendiente_aplicacion' && $detalle->inventario_movimiento_id !== null
            && $detalle->inventario_documento_detalle_id !== null && $detalle->importe_mxn !== null;
    }

    public static function vinculosIncompletos(string $estado, $detalle): bool
    {
        if ($estado !== 'pendiente_aplicacion') return false;
        $presentes = (int) ($detalle->inventario_movimiento_id !== null)
            + (int) ($detalle->inventario_documento_detalle_id !== null) + (int) ($detalle->importe_mxn !== null);
        return $presentes !== 0 && $presentes !== 3;
    }
}
