<?php

namespace App\Services\Obras;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterialEntregadoTrazabilidadService
{
    /** Adjunta referencias documentadas sin duplicar ni modificar las cantidades entregadas. */
    public function anexar(Collection $entregas, int $obraId): Collection
    {
        $directas = $entregas->where('origen_tipo', 'recepcion_directa')->pluck('origen_id');
        $generales = $entregas->where('origen_tipo', 'salida_general')->pluck('origen_id');
        $huentitan = $entregas->where('origen_tipo', 'salida_huentitan')->pluck('origen_id');

        $referenciasDirectas = DB::table('compra_recepcion_directa_detalles as p')
            ->join('compra_recepciones_directas as r', 'r.id', '=', 'p.recepcion_id')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'r.orden_compra_id')
            ->leftJoin('obra_requisicion_detalles as rd', function ($join) {
                $join->on('rd.id', '=', 'p.obra_requisicion_detalle_id')->on('rd.item_id', '=', 'p.producto_id')->where('rd.item_type', '=', 'producto');
            })
            ->leftJoin('obra_requisiciones as rq', function ($join) use ($obraId) {
                $join->on('rq.id', '=', 'rd.obra_requisicion_id')->where('rq.obra_id', '=', $obraId);
            })
            ->leftJoin('inventario_documentos as salida', 'salida.id', '=', 'r.salida_documento_id')
            ->whereIn('p.id', $directas)->where('r.obra_id', $obraId)->where('oc.obra_id', $obraId)
            ->select('p.id', 'oc.id as oc_id', 'oc.folio as oc_folio', 'rq.id as rq_id', 'rq.folio as rq_folio',
                'salida.id as salida_id', 'salida.folio as salida_folio', 'r.id as recepcion_id', 'r.folio as recepcion_folio')
            ->get()->keyBy('id');

        // La OC de cabecera es antecedente del documento; no se infiere que una partida concreta proceda de ella.
        $referenciasGenerales = DB::table('inventario_movimientos as m')
            ->join('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->leftJoin('ordenes_compra as oc', function ($join) use ($obraId) {
                $join->on('oc.id', '=', 'd.orden_compra_id')->where('oc.obra_id', '=', $obraId);
            })->whereIn('m.id', $generales)->where('m.obra_id', $obraId)
            ->select('m.id', 'd.id as salida_id', 'd.folio as salida_folio', 'oc.id as oc_id', 'oc.folio as oc_folio')
            ->get()->keyBy('id');

        // Una salida Huentitán puede relacionarse con varias partidas y órdenes.
        $referenciasHuentitan = DB::table('orden_compra_detalles as cd')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'cd.orden_compra_id')
            ->join('huentitan_salida_detalles as sd', 'sd.id', '=', 'cd.huentitan_salida_detalle_id')
            ->join('huentitan_salidas as s', 's.id', '=', 'sd.huentitan_salida_id')
            ->leftJoin('obra_requisicion_detalles as rd', function ($join) {
                $join->on('rd.id', '=', 'cd.obra_requisicion_detalle_id')->on('rd.item_id', '=', 'sd.producto_id')->where('rd.item_type', '=', 'producto');
            })
            ->leftJoin('obra_requisiciones as rq', function ($join) use ($obraId) {
                $join->on('rq.id', '=', 'rd.obra_requisicion_id')->where('rq.obra_id', '=', $obraId);
            })
            ->whereIn('sd.id', $huentitan)->where('s.obra_id', $obraId)->where('oc.obra_id', $obraId)
            ->whereColumn('cd.producto_id', 'sd.producto_id')
            ->select('sd.id as origen_id', 'cd.id as compra_detalle_id', 'oc.id as oc_id', 'oc.folio as oc_folio',
                'rq.id as rq_id', 'rq.folio as rq_folio')->get()->groupBy('origen_id');

        return $entregas->each(function ($item) use ($referenciasDirectas, $referenciasGenerales, $referenciasHuentitan) {
            $item->ordenes_compra = [];
            $item->requisiciones = [];
            $item->salida = ['tipo' => $item->origen_tipo === 'salida_huentitan' ? 'huentitan' : 'inventario', 'id' => $item->documento_id, 'folio' => $item->folio];
            $item->recepcion = null;
            if ($item->origen_tipo === 'recepcion_directa' && ($ref = $referenciasDirectas->get($item->origen_id))) {
                $item->ordenes_compra[] = ['id' => $ref->oc_id, 'folio' => $ref->oc_folio, 'alcance' => 'partida'];
                if ($ref->rq_id) $item->requisiciones[] = ['id' => $ref->rq_id, 'folio' => $ref->rq_folio, 'alcance' => 'partida'];
                $item->salida = ['tipo' => 'inventario', 'id' => $ref->salida_id, 'folio' => $ref->salida_folio];
                $item->recepcion = ['id' => $ref->recepcion_id, 'folio' => $ref->recepcion_folio];
            } elseif ($item->origen_tipo === 'salida_general' && ($ref = $referenciasGenerales->get($item->origen_id))) {
                if ($ref->oc_id) $item->ordenes_compra[] = ['id' => $ref->oc_id, 'folio' => $ref->oc_folio, 'alcance' => 'documento'];
            } elseif ($item->origen_tipo === 'salida_huentitan') {
                foreach ($referenciasHuentitan->get($item->origen_id, collect()) as $ref) {
                    $item->ordenes_compra[] = ['id' => $ref->oc_id, 'folio' => $ref->oc_folio, 'alcance' => 'partida_compra', 'partida_id' => $ref->compra_detalle_id];
                    if ($ref->rq_id) $item->requisiciones[] = ['id' => $ref->rq_id, 'folio' => $ref->rq_folio, 'alcance' => 'partida_compra', 'partida_id' => $ref->compra_detalle_id];
                }
            }
        });
    }
}
