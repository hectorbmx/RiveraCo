<?php

namespace App\Services\Obras;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterialEntregadoConsultaService
{
    /** Movimientos que requieren revisión antes de atribuirlos a una salida general. */
    public function ambiguasPorObra(int $obraId): Collection
    {
        return DB::table('inventario_movimientos as m')
            ->join('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->where('m.obra_id', $obraId)->whereColumn('d.obra_id', 'm.obra_id')
            ->whereColumn('d.almacen_id', 'm.almacen_id')->where('m.tipo_movimiento', 'out')
            ->where('d.tipo', 'salida')->where('d.estado', 'aplicado')->whereNull('d.deleted_at')
            ->whereNull('m.compra_recepcion_directa_id')->whereNull('d.compra_recepcion_directa_id')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('huentitan_salidas as hs')
                ->whereColumn('hs.id', 'm.documento_id')->whereColumn('hs.almacen_id', 'm.almacen_id'))
            ->select('m.id as movimiento_id', 'm.documento_id', 'm.almacen_id', 'm.producto_id', 'm.fecha', 'm.cantidad')
            ->get();
    }

    /** Entregas brutas aplicadas; aún no calcula saldos de devolución. */
    public function porObra(int $obraId): Collection
    {
        $directas = DB::table('compra_recepcion_directa_detalles as p')
            ->join('compra_recepciones_directas as r', 'r.id', '=', 'p.recepcion_id')
            ->join('inventario_documentos as d', 'd.id', '=', 'r.salida_documento_id')
            ->where('r.obra_id', $obraId)->where('r.estado', 'aplicada')
            ->where('d.estado', 'aplicado')->where('d.tipo', 'salida')->whereNull('d.deleted_at')
            ->whereColumn('d.compra_recepcion_directa_id', 'r.id')->whereColumn('d.obra_id', 'r.obra_id')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('inventario_movimientos as m')
                ->whereColumn('m.documento_id', 'd.id')->whereColumn('m.compra_recepcion_directa_detalle_id', 'p.id')
                ->whereColumn('m.compra_recepcion_directa_id', 'r.id')->whereColumn('m.producto_id', 'p.producto_id')
                ->whereColumn('m.obra_id', 'r.obra_id')->where('m.tipo_movimiento', 'out'))
            ->selectRaw("'recepcion_directa' as origen_tipo, p.id as origen_id, r.id as documento_id, r.folio, r.fecha_recepcion as fecha, r.almacen_id, p.producto_id, p.descripcion, p.unidad_inventario as unidad, p.cantidad_inventario as cantidad, p.costo_unitario_mxn as costo_unitario_mxn, p.importe_mxn, 'historica' as unidad_fuente")
            ->get();

        $generales = DB::table('inventario_movimientos as m')
            ->join('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->join('productos as p', 'p.id', '=', 'm.producto_id')
            ->where('m.obra_id', $obraId)->whereColumn('d.obra_id', 'm.obra_id')
            ->whereColumn('d.almacen_id', 'm.almacen_id')->where('m.tipo_movimiento', 'out')
            ->where('d.tipo', 'salida')->where('d.estado', 'aplicado')->whereNull('d.deleted_at')
            ->whereNull('m.compra_recepcion_directa_id')->whereNull('d.compra_recepcion_directa_id')
            // Los identificadores de Huentitán comparten documento_id; una colisión es ambigua.
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('huentitan_salidas as hs')
                ->whereColumn('hs.id', 'm.documento_id')->whereColumn('hs.almacen_id', 'm.almacen_id'))
            ->selectRaw("'salida_general' as origen_tipo, m.id as origen_id, d.id as documento_id, d.folio, m.fecha, m.almacen_id, m.producto_id, p.nombre as descripcion, COALESCE(p.unidad_base, p.unidad) as unidad, m.cantidad, m.costo_unitario as costo_unitario_mxn, COALESCE(m.importe_costo, m.cantidad * m.costo_unitario) as importe_mxn, 'catalogo_actual' as unidad_fuente")
            ->get();

        $huentitan = DB::table('huentitan_salida_detalles as p')
            ->join('huentitan_salidas as s', 's.id', '=', 'p.huentitan_salida_id')
            ->where('s.obra_id', $obraId)->where('s.estado', 'aplicada')
            ->whereNotNull('p.producto_id')->where('p.cantidad_salida', '>', 0)
            ->selectRaw("'salida_huentitan' as origen_tipo, p.id as origen_id, s.id as documento_id, s.folio, s.fecha, s.almacen_id, p.producto_id, p.descripcion, p.unidad, p.cantidad_salida as cantidad, p.costo_unitario as costo_unitario_mxn, p.importe as importe_mxn, 'historica' as unidad_fuente")
            ->get();

        $entregas = $directas->concat($generales)->concat($huentitan)->sortByDesc('fecha')->values();
        return app(MaterialEntregadoTrazabilidadService::class)->anexar($entregas, $obraId);
    }
}
