<?php

namespace App\Services\Compras;

use App\Models\CompraRecepcionDirecta;
use Illuminate\Support\Facades\DB;

class CompraRecepcionDirectaObraService
{
    public function resumen(int $obraId): array
    {
        $importe = 'COALESCE(m.importe_costo, m.cantidad * m.costo_unitario)';
        $partidas = DB::table('inventario_movimientos as m')
            ->join('compra_recepciones_directas as r', 'r.id', '=', 'm.compra_recepcion_directa_id')
            ->join('compra_recepcion_directa_detalles as p', 'p.id', '=', 'm.compra_recepcion_directa_detalle_id')
            ->join('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'r.orden_compra_id')
            ->where('r.obra_id', $obraId)->whereColumn('m.obra_id', 'r.obra_id')
            ->whereColumn('p.recepcion_id', 'r.id')
            ->where('r.estado', CompraRecepcionDirecta::ESTADO_APLICADA)
            ->where('d.estado', 'aplicado')->whereNull('d.deleted_at')
            ->where(function ($query) {
                $query->where(function ($out) {
                    $out->where('m.tipo_movimiento', 'out')->whereColumn('m.documento_id', 'r.salida_documento_id');
                })->orWhere(function ($back) {
                    $back->where('m.tipo_movimiento', 'in')->where('d.tipo', 'devolucion');
                });
            })
            ->select(['p.id', 'p.descripcion', 'p.unidad_inventario', 'r.id as recepcion_id', 'r.folio', 'r.fecha_recepcion',
                'oc.id as orden_compra_id', 'oc.folio as orden_compra_folio'])
            ->selectRaw("SUM(CASE WHEN m.tipo_movimiento = 'out' THEN m.cantidad ELSE -m.cantidad END) as cantidad_neta")
            ->selectRaw("SUM(CASE WHEN m.tipo_movimiento = 'out' THEN {$importe} ELSE -({$importe}) END) as importe_neto")
            ->groupBy('p.id', 'p.descripcion', 'p.unidad_inventario', 'r.id', 'r.folio', 'r.fecha_recepcion', 'oc.id', 'oc.folio')
            ->orderByDesc('r.id')->orderBy('p.id')->get();

        return ['partidas' => $partidas, 'total_mxn' => round((float) $partidas->sum('importe_neto'), 2)];
    }
}
