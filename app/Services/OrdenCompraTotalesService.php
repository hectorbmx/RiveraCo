<?php

namespace App\Services;

use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraTotalesService
{
    public static function recalcular(OrdenCompra $oc): void
    {
        $tot = DB::table('orden_compra_detalles')
            ->where('orden_compra_id', $oc->id)
            ->selectRaw('
                COALESCE(SUM(importe),0) as subtotal,
                COALESCE(SUM(iva),0) as iva,
                COALESCE(SUM(otros_impuestos),0) as otros_impuestos,
                COALESCE(SUM(retenciones),0) as retenciones
            ')
            ->first();

        $subtotal = (float) $tot->subtotal;
        $iva      = (float) $tot->iva;
        $otros    = (float) $tot->otros_impuestos;
        $ret      = (float) $tot->retenciones;

        // Total legacy: subtotal + iva + otros - retenciones
        $total = max(0, $subtotal + $iva + $otros - $ret);

        $oc->subtotal = $subtotal;
        $oc->iva = $iva;
        $oc->otros_impuestos = $otros;
        $oc->total = $total;
        $oc->save();
    }
}
