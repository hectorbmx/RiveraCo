<?php

namespace App\Services;

use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraTotalesService
{
    // public static function recalcular(OrdenCompra $oc): void
    // {
    //     $tot = DB::table('orden_compra_detalles')
    //         ->where('orden_compra_id', $oc->id)
    //         ->selectRaw('
    //             COALESCE(SUM(importe),0) as subtotal,
    //             COALESCE(SUM(iva),0) as iva,
    //             COALESCE(SUM(otros_impuestos),0) as otros_impuestos,
    //             COALESCE(SUM(retenciones),0) as retenciones
    //         ')
    //         ->first();

    //     $subtotal = (float) $tot->subtotal;
    //     $iva      = (float) $tot->iva;
    //     $otros    = (float) $tot->otros_impuestos;
    //     $ret      = (float) $tot->retenciones;

    //     // Total legacy: subtotal + iva + otros - retenciones
    //     $total = max(0, $subtotal + $iva + $otros - $ret);

    //     $oc->subtotal = $subtotal;
    //     $oc->iva = $iva;
    //     $oc->otros_impuestos = $otros;
    //     $oc->total = $total;
    //     $oc->save();
    // }
    public static function recalcular(OrdenCompra $oc): void
{
    $tot = DB::table('orden_compra_detalles')
        ->where('orden_compra_id', $oc->id)
        ->selectRaw('
            COALESCE(SUM(importe),0) as subtotal,
            COALESCE(SUM(importe * (iva/100)),0) as iva_monto,
            COALESCE(SUM(otros_impuestos),0) as otros_impuestos,
            COALESCE(SUM(retenciones),0) as retenciones
        ')
        ->first();

    $subtotal  = (float) $tot->subtotal;
    $ivaMonto  = (float) $tot->iva_monto;
    $otros     = (float) $tot->otros_impuestos;
    $ret       = (float) $tot->retenciones;

    $total = max(0, $subtotal + $ivaMonto + $otros - $ret);

    $oc->subtotal = $subtotal;

    // ✅ NO PISAR el % base de cabecera ($oc->iva)
    // $oc->iva se queda como 16 (o el que hayas puesto en create)

    $oc->otros_impuestos = $otros;
    $oc->total = $total;

    $oc->save();
}

}
