<?php

namespace App\Services\Obras;

use App\Models\ObraDevolucion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ObraDevolucionPendienteConsultaService
{
    public function consultar(?string $busqueda = null, ?int $solicitudId = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $busqueda = trim((string) $busqueda);
        $solicitudes = ObraDevolucion::query()->whereIn('estado', ['solicitada', 'recepcion_parcial'])
            ->when($solicitudId, fn ($q) => $q->whereKey($solicitudId))
            ->with(['obra', 'presentadoPor', 'recepciones.almacen', 'recepciones.detalles', 'detalles.producto', 'detalles.recepcionDirectaDetalle.recepcion.almacen',
                'detalles.movimientoOrigen.documento', 'detalles.movimientoOrigen.almacen',
                'detalles.salidaHuentitanDetalle.salida.almacen', 'detalles.recepcionesDetalle.recepcion'])
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($w) => $w->where('folio', 'like', '%'.$busqueda.'%')
                ->orWhereHas('obra', fn ($o) => $o->where('nombre', 'like', '%'.$busqueda.'%')->orWhere('clave_obra', 'like', '%'.$busqueda.'%'))))
            ->orderBy('presentado_at')->orderBy('id')->paginate(20, ['*'], $pageName)->withQueryString();

        $documentos = $solicitudes->getCollection()->flatMap(fn ($s) => $s->detalles)
            ->map(fn ($d) => $d->movimientoOrigen?->documento_id)->filter()->unique();
        $colisiones = DB::table('huentitan_salidas')->whereIn('id', $documentos)->get(['id', 'almacen_id'])
            ->keyBy(fn ($s) => $s->id.':'.$s->almacen_id);

        foreach ($solicitudes as $solicitud) {
            foreach ($solicitud->detalles as $partida) {
                $recibido = 0; $errores = [];
                foreach ($partida->recepcionesDetalle as $detalleRecepcion) {
                    $recepcion = $detalleRecepcion->recepcion;
                    if (! $recepcion || ! in_array($recepcion->estado, ['aplicada', 'aplicado', 'pendiente_aplicacion'], true)) continue;
                    $cantidad = (int) round((float) $detalleRecepcion->cantidad_recibida * 1000);
                    if ((int) $recepcion->devolucion_id !== (int) $solicitud->id || $cantidad <= 0) $errores[] = 'Recepción inconsistente con la solicitud.';
                    if (ObraDevolucionEstadoPartida::vinculosIncompletos($recepcion->estado, $detalleRecepcion)) $errores[] = 'Vínculos de aplicación incompletos.';
                    $recibido += $cantidad;
                }
                $solicitado = (int) round((float) $partida->cantidad_solicitada * 1000);
                if ($solicitado <= 0 || $recibido > $solicitado) $errores[] = 'Cantidad recibida inconsistente.';
                $origen = null; $almacen = null;
                if ($partida->recepcion_directa_detalle_id) {
                    $detalle = $partida->recepcionDirectaDetalle; $salida = $detalle?->recepcion;
                    $origen = $salida?->folio; $almacen = $salida?->almacen?->nombre;
                    if (! $salida || $salida->estado !== 'aplicada' || (int) $salida->obra_id !== (int) $solicitud->obra_id || (int) $detalle->producto_id !== (int) $partida->producto_id) $errores[] = 'Entrega directa no disponible o inconsistente.';
                } elseif ($partida->inventario_movimiento_id) {
                    $movimiento = $partida->movimientoOrigen; $doc = $movimiento?->documento;
                    $origen = $doc?->folio; $almacen = $movimiento?->almacen?->nombre;
                    if (! $movimiento || ! $doc || $doc->tipo !== 'salida' || $doc->estado !== 'aplicado' || $movimiento->tipo_movimiento !== 'out'
                        || (int) $doc->obra_id !== (int) $solicitud->obra_id || (int) $movimiento->obra_id !== (int) $solicitud->obra_id
                        || (int) $doc->almacen_id !== (int) $movimiento->almacen_id || (int) $movimiento->producto_id !== (int) $partida->producto_id
                        || $movimiento->compra_recepcion_directa_id || $colisiones->has($movimiento->documento_id.':'.$movimiento->almacen_id)) $errores[] = 'Salida de origen no disponible o ambigua.';
                } elseif ($partida->huentitan_salida_detalle_id) {
                    $detalle = $partida->salidaHuentitanDetalle; $salida = $detalle?->salida;
                    $origen = $salida?->folio; $almacen = $salida?->almacen?->nombre;
                    if (! $salida || $salida->estado !== 'aplicada' || (int) $salida->obra_id !== (int) $solicitud->obra_id || (int) $detalle->producto_id !== (int) $partida->producto_id) $errores[] = 'Salida Huentitán no disponible o inconsistente.';
                } else $errores[] = 'Origen de entrega no identificado.';
                $partida->setAttribute('consulta_origen', $origen ?: 'No disponible');
                $partida->setAttribute('consulta_almacen_origen', $almacen ?: 'No disponible');
                $partida->setAttribute('consulta_recibido', $recibido / 1000.0);
                $partida->setAttribute('consulta_pendiente', $errores ? null : ($solicitado - $recibido) / 1000.0);
                $partida->setAttribute('consulta_errores', array_values(array_unique($errores)));
            }
        }
        app(ObraDevolucionDiferenciasConsultaService::class)->anexar($solicitudes->getCollection());
        return $solicitudes;
    }

    public function consultarPreparadas(?string $busqueda = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $solicitudes = $this->consultar($busqueda, null, $pageName);
        foreach ($solicitudes as $solicitud) {
            foreach ($solicitud->recepciones->where('estado', 'pendiente_aplicacion') as $recepcion) {
                try {
                    $recepcion->setAttribute('entrada_preparada', app(ObraDevolucionEntradaPreparacionService::class)->preparar($recepcion, $solicitud));
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $recepcion->setAttribute('entrada_requiere_revision', true);
                }
            }
        }
        return $solicitudes;
    }
}
