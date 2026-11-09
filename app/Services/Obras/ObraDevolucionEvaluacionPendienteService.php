<?php

namespace App\Services\Obras;

use App\Models\Obra;
use App\Models\ObraDevolucion;
use App\Models\ObraDevolucionRecepcion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObraDevolucionEvaluacionPendienteService
{
    /** Registra custodia física segregada; no crea existencia ni determina valuación. */
    public function registrar(ObraDevolucionRecepcion $recepcion, User $usuario): int
    {
        if (! $usuario->can('obra.devoluciones.view.access') || ! $usuario->can('obra.devoluciones.receive.access')) throw new AuthorizationException();
        return DB::transaction(function () use ($recepcion, $usuario) {
            $solicitudId = $recepcion->devolucion_id;
            $obraId = ObraDevolucion::whereKey($solicitudId)->value('obra_id');
            Obra::whereKey($obraId)->lockForUpdate()->firstOrFail();
            $solicitud = ObraDevolucion::whereKey($solicitudId)->lockForUpdate()->firstOrFail();
            $recepcion = ObraDevolucionRecepcion::whereKey($recepcion->id)->where('devolucion_id', $solicitud->id)->lockForUpdate()->firstOrFail();
            if ($recepcion->estado !== 'pendiente_aplicacion') throw ValidationException::withMessages(['recepcion' => 'La recepción no está pendiente de aplicación.']);
            $detalles = $recepcion->detalles()->orderBy('id')->lockForUpdate()->get();
            $consulta = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $solicitud->id)->firstOrFail();
            $partidas = $consulta->detalles->keyBy('id');
            $productos = DB::table('productos')->whereIn('id', $partidas->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $registradas = 0;
            foreach ($detalles as $detalle) {
                if (! in_array($detalle->condicion_recibida, ['danado', 'desgastado'], true)) continue;
                $partida = $partidas->get($detalle->devolucion_detalle_id);
                if (! $partida || $partida->consulta_pendiente === null || (float) $detalle->cantidad_recibida <= 0
                    || $detalle->inventario_movimiento_id || $detalle->inventario_documento_detalle_id || $detalle->importe_mxn !== null) {
                    throw ValidationException::withMessages(['recepcion' => 'Una partida tiene origen, cantidad o movimientos inconsistentes; requiere revisión.']);
                }
                if ($detalle->estado_evaluacion === 'pendiente_evaluacion') {
                    if ($detalle->evaluacion_tipo_snapshot !== 'herramienta') throw ValidationException::withMessages(['recepcion' => 'La clasificación registrada requiere revisión.']);
                    continue;
                }
                if ($detalle->estado_evaluacion !== null) throw ValidationException::withMessages(['recepcion' => 'Una partida ya tiene otro estado de evaluación.']);
                if (($productos->get($partida->producto_id)->tipo_inventario ?? null) !== 'herramienta') continue;
                $detalle->forceFill(['estado_evaluacion' => 'pendiente_evaluacion', 'evaluacion_tipo_snapshot' => 'herramienta',
                    'evaluacion_registrada_por' => $usuario->id, 'evaluacion_registrada_at' => now()])->save();
                $registradas++;
            }
            if (! $registradas && ! $detalles->contains(fn ($d) => $d->estado_evaluacion === 'pendiente_evaluacion')) throw ValidationException::withMessages(['recepcion' => 'No hay herramientas dañadas o desgastadas identificadas en el catálogo.']);
            return $registradas;
        }, 3);
    }
}
