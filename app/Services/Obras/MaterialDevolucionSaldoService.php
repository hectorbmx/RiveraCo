<?php

namespace App\Services\Obras;

use Illuminate\Support\Facades\DB;

class MaterialDevolucionSaldoService
{
    /** Consulta informativa; presentar/recibir debe repetir la validación bajo bloqueo. */
    public function porObra(int $obraId): array
    {
        $consulta = app(MaterialEntregadoConsultaService::class);
        $entregas = $consulta->porObra($obraId);
        $partidas = DB::table('obra_devolucion_detalles as p')
            ->join('obra_devoluciones as d', 'd.id', '=', 'p.devolucion_id')
            ->where('d.obra_id', $obraId)
            ->select('p.*', 'd.estado as solicitud_estado')->get();
        $recepciones = DB::table('obra_devolucion_recepcion_detalles as p')
            ->join('obra_devolucion_recepciones as r', 'r.id', '=', 'p.recepcion_id')
            ->whereIn('p.devolucion_detalle_id', $partidas->pluck('id'))
            ->select('p.devolucion_detalle_id', 'p.cantidad_recibida', 'p.estado_evaluacion', 'p.importe_mxn', 'p.inventario_movimiento_id', 'p.inventario_documento_detalle_id', 'r.estado', 'r.devolucion_id')
            ->get()->groupBy('devolucion_detalle_id');
        // Una devolución anterior sin referencia inequívoca impide asignar el saldo a una entrega concreta.
        $devolucionesSinOrigen = DB::table('inventario_movimientos as m')
            ->join('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->where('m.obra_id', $obraId)->whereColumn('d.obra_id', 'm.obra_id')
            ->whereColumn('d.almacen_id', 'm.almacen_id')->where('m.tipo_movimiento', 'in')
            ->where('d.tipo', 'devolucion')->where('d.estado', 'aplicado')->whereNull('d.deleted_at')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('obra_devolucion_recepcion_detalles as rd')
                ->whereColumn('rd.inventario_movimiento_id', 'm.id'))
            ->select('m.id', 'm.producto_id', 'm.almacen_id', 'm.cantidad', 'm.documento_id')->get();
        $sinOrigenPorProducto = $devolucionesSinOrigen->groupBy('producto_id');
        $porOrigen = [];
        $inconsistencias = [];
        $campos = ['recepcion_directa' => 'recepcion_directa_detalle_id', 'salida_general' => 'inventario_movimiento_id', 'salida_huentitan' => 'huentitan_salida_detalle_id'];
        foreach ($partidas as $partida) {
            foreach ($campos as $tipo => $campo) if ($partida->$campo !== null) $porOrigen[$tipo.':'.$partida->$campo][] = $partida;
        }
        $vistos = [];
        foreach ($entregas as $entrega) {
            $clave = $entrega->origen_tipo.':'.$entrega->origen_id;
            $vistos[$clave] = true;
            $entregado = (int) round((float) $entrega->cantidad * 1000);
            $recibido = 0; $comprometido = 0; $evaluacion = 0; $evaluacionAplicada = 0; $importeDevuelto = 0; $errores = [];
            if ($sinOrigenPorProducto->has($entrega->producto_id)) $errores[] = 'Existe una devolución previa sin origen identificable para este producto.';
            foreach ($porOrigen[$clave] ?? [] as $partida) {
                $confirmado = 0; $evaluacionPendientePartida = 0;
                foreach ($recepciones->get($partida->id, collect()) as $recepcion) {
                    if (ObraDevolucionEstadoPartida::vinculosIncompletos($recepcion->estado, $recepcion)) $errores[] = 'Partida de recepción con vínculos de aplicación incompletos.';
                    $aplicada = ObraDevolucionEstadoPartida::aplicada($recepcion->estado, $recepcion);
                    if ($recepcion->estado === 'pendiente_aplicacion' && ! $aplicada && $recepcion->estado_evaluacion === 'pendiente_evaluacion') {
                        if ((int) $recepcion->devolucion_id !== (int) $partida->devolucion_id || (float) $recepcion->cantidad_recibida <= 0) $errores[] = 'Recepción pendiente de evaluación inconsistente.';
                        $pendiente = (int) round((float) $recepcion->cantidad_recibida * 1000);
                        $evaluacion += $pendiente; $evaluacionPendientePartida += $pendiente;
                        continue;
                    }
                    if (! $aplicada) continue;
                    $cantidad = (int) round((float) $recepcion->cantidad_recibida * 1000);
                    if ((int) $recepcion->devolucion_id !== (int) $partida->devolucion_id || $cantidad <= 0) $errores[] = 'Recepción inconsistente con la solicitud.';
                    $confirmado += $cantidad;
                    if ($recepcion->estado_evaluacion !== 'pendiente_evaluacion' && $recepcion->inventario_movimiento_id && $recepcion->importe_mxn !== null) $importeDevuelto += (float) $recepcion->importe_mxn;
                    if ($recepcion->estado_evaluacion === 'pendiente_evaluacion') { $evaluacion += $cantidad; $evaluacionAplicada += $cantidad; }
                }
                $solicitado = (int) round((float) $partida->cantidad_solicitada * 1000);
                if ((int) $partida->producto_id !== (int) $entrega->producto_id || $solicitado <= 0 || $confirmado + $evaluacionPendientePartida > $solicitado) $errores[] = 'La partida solicitada no coincide con la entrega o excede su cantidad.';
                if (in_array($partida->solicitud_estado, ['solicitada', 'recepcion_parcial', 'recibida'], true)) {
                    $recibido += $confirmado;
                    $comprometido += max(0, $solicitado - $confirmado);
                    if ($partida->solicitud_estado === 'recibida' && $confirmado !== $solicitado) $errores[] = 'Una solicitud recibida conserva cantidad pendiente.';
                } elseif (! in_array($partida->solicitud_estado, ['borrador', 'cancelada'], true) || $confirmado !== 0) {
                    $errores[] = 'El estado de solicitud no concuerda con las recepciones.';
                }
            }
            if ($entregado <= 0 || $recibido + $comprometido > $entregado || $evaluacion > $entregado || $evaluacionAplicada > $recibido) $errores[] = 'Las cantidades superan o no corresponden a la entrega.';
            $entrega->cantidad_devuelta_confirmada = $recibido / 1000.0;
            $entrega->cantidad_pendiente_evaluacion = $evaluacion / 1000.0;
            $entrega->importe_devuelto_mxn = round($importeDevuelto, 2);
            $entrega->importe_neto_mxn = $entrega->importe_mxn === null ? null : round((float) $entrega->importe_mxn - $importeDevuelto, 2);
            $entrega->cantidad_comprometida = $comprometido / 1000.0;
            $entrega->cantidad_disponible_solicitar = $errores ? null : ($entregado - $recibido - $comprometido) / 1000.0;
            $entrega->requiere_revision = (bool) $errores;
            $entrega->inconsistencias = array_values(array_unique($errores));
            if ($errores) $inconsistencias[] = ['origen' => $clave, 'motivos' => $entrega->inconsistencias];
        }
        foreach ($porOrigen as $clave => $solicitudes) {
            if (! isset($vistos[$clave]) && collect($solicitudes)->contains(fn ($p) => ! in_array($p->solicitud_estado, ['borrador', 'cancelada'], true)
                || $recepciones->get($p->id, collect())->contains(fn ($r) => in_array($r->estado, ['aplicada', 'aplicado'], true)))) {
                $inconsistencias[] = ['origen' => $clave, 'motivos' => ['La entrega vinculada no está disponible como aplicada en esta obra.']];
            }
        }
        return ['entregas' => $entregas, 'origenes_ambiguos' => $consulta->ambiguasPorObra($obraId), 'devoluciones_sin_origen' => $devolucionesSinOrigen, 'inconsistencias' => $inconsistencias];
    }
}
