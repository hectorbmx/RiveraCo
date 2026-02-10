<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioKardexGerencialController extends Controller
{
    public function producto(Request $request, int $producto)
    {
        $request->validate([
            'almacen_id' => ['nullable', 'integer'],
            'desde'      => ['nullable', 'date'],
            'hasta'      => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $almacenId = $request->integer('almacen_id');
        $desde     = $request->input('desde');
        $hasta     = $request->input('hasta');

        $q = DB::table('inventario_movimientos as m')
            ->leftJoin('productos as p', 'p.id', '=', 'm.producto_id')
            ->leftJoin('almacenes as a', 'a.id', '=', 'm.almacen_id')
            ->leftJoin('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->where('m.producto_id', $producto)
            ->select([
                'm.id',
                'm.fecha',
                'm.tipo_movimiento',
                'm.cantidad',
                'm.costo_unitario',
                'm.saldo_cantidad',
                'm.almacen_id',
                'm.documento_id',
                'p.sku as producto_sku',
                DB::raw("COALESCE(p.nombre, p.descripcion) as producto_nombre"),
                'a.nombre as almacen_nombre',
                'd.tipo as documento_tipo',
                'd.estado as documento_estado',
            ])
            ->orderByDesc('m.id');

        if ($almacenId) {
            $q->where('m.almacen_id', $almacenId);
        }
        if ($desde) {
            $q->whereDate('m.fecha', '>=', $desde);
        }
        if ($hasta) {
            $q->whereDate('m.fecha', '<=', $hasta);
        }

        $perPage = min(max((int) $request->get('per_page', 30), 1), 50);
        $rows = $q->paginate($perPage)->withQueryString();

        // Cabecera del producto (para título de pantalla)
        $productoInfo = DB::table('productos')
            ->where('id', $producto)
            ->first(['id','sku', DB::raw("COALESCE(nombre, descripcion) as nombre")]);

        return response()->json([
            'ok' => true,
            'producto' => $productoInfo ? [
                'id' => (int) $productoInfo->id,
                'sku' => $productoInfo->sku ?? null,
                'nombre' => $productoInfo->nombre ?? null,
            ] : [
                'id' => (int) $producto,
                'sku' => null,
                'nombre' => null,
            ],
            'data' => collect($rows->items())->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'fecha' => $r->fecha ? (string) $r->fecha : null,
                    'tipo_movimiento' => $r->tipo_movimiento, // entrada/salida/cancelacion/etc
                    'cantidad' => $r->cantidad !== null ? (float) $r->cantidad : null,
                    'costo_unitario' => $r->costo_unitario !== null ? (float) $r->costo_unitario : null,
                    'saldo_cantidad' => $r->saldo_cantidad !== null ? (float) $r->saldo_cantidad : null,
                    'almacen' => [
                        'id' => $r->almacen_id !== null ? (int) $r->almacen_id : null,
                        'nombre' => $r->almacen_nombre ?? null,
                    ],
                    'documento' => [
                        'id' => $r->documento_id !== null ? (int) $r->documento_id : null,
                        'tipo' => $r->documento_tipo ?? null,
                        'estado' => $r->documento_estado ?? null,
                    ],
                ];
            })->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }
    public function resumenProducto(Request $request, $producto)
{
    $producto = (int) $producto;

    $request->validate([
        'almacen_id' => ['nullable', 'integer'],
        'desde'      => ['nullable', 'date'],
        'hasta'      => ['nullable', 'date', 'after_or_equal:desde'],
        'days'       => ['nullable', 'integer', 'min:1', 'max:365'],
    ]);

    $almacenId = $request->integer('almacen_id');
    $desde     = $request->input('desde');
    $hasta     = $request->input('hasta');

    // alternativa rápida: ventana por days si no mandan desde/hasta
    if (!$desde && !$hasta && $request->filled('days')) {
        $desde = now()->subDays((int)$request->days)->toDateString();
        $hasta = now()->toDateString();
    }

    $base = DB::table('inventario_movimientos as m')
        ->where('m.producto_id', $producto);

    if ($almacenId) $base->where('m.almacen_id', $almacenId);
    if ($desde) $base->whereDate('m.fecha', '>=', $desde);
    if ($hasta) $base->whereDate('m.fecha', '<=', $hasta);

    // Entradas/salidas por tipo_movimiento (ajusta strings si en tu DB difieren)
    $kpis = (clone $base)->selectRaw("
        COUNT(*) as movimientos_total,
        COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'entrada' THEN m.cantidad ELSE 0 END),0) as entradas_cantidad,
        COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'salida' THEN m.cantidad ELSE 0 END),0) as salidas_cantidad,
        COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'entrada' THEN (m.cantidad * m.costo_unitario) ELSE 0 END),0) as entradas_valor,
        COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'salida' THEN (m.cantidad * m.costo_unitario) ELSE 0 END),0) as salidas_valor,
        MAX(m.fecha) as ultimo_movimiento_fecha
    ")->first();

    // Último movimiento (1 row)
    $ultimo = (clone $base)
        ->leftJoin('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
        ->orderByDesc('m.id')
        ->first([
            'm.id',
            'm.fecha',
            'm.tipo_movimiento',
            'm.cantidad',
            'm.costo_unitario',
            'm.saldo_cantidad',
            'm.almacen_id',
            'm.documento_id',
            'd.tipo as documento_tipo',
            'd.estado as documento_estado',
        ]);

    // Cabecera del producto
    $productoInfo = DB::table('productos')
        ->where('id', $producto)
        ->first(['id','sku', DB::raw("COALESCE(nombre, descripcion) as nombre")]);

    return response()->json([
        'ok' => true,
        'filters' => [
            'producto_id' => (int) $producto,
            'almacen_id' => $almacenId,
            'desde' => $desde,
            'hasta' => $hasta,
        ],
        'producto' => $productoInfo ? [
            'id' => (int) $productoInfo->id,
            'sku' => $productoInfo->sku ?? null,
            'nombre' => $productoInfo->nombre ?? null,
        ] : [
            'id' => (int) $producto,
            'sku' => null,
            'nombre' => null,
        ],
        'kpis' => [
            'movimientos_total' => (int) ($kpis->movimientos_total ?? 0),
            'entradas_cantidad' => round((float) ($kpis->entradas_cantidad ?? 0), 3),
            'salidas_cantidad' => round((float) ($kpis->salidas_cantidad ?? 0), 3),
            'neto_cantidad' => round((float) (($kpis->entradas_cantidad ?? 0) - ($kpis->salidas_cantidad ?? 0)), 3),
            'entradas_valor' => round((float) ($kpis->entradas_valor ?? 0), 2),
            'salidas_valor' => round((float) ($kpis->salidas_valor ?? 0), 2),
        ],
        'ultimo_movimiento' => $ultimo ? [
            'id' => (int) $ultimo->id,
            'fecha' => $ultimo->fecha ? (string) $ultimo->fecha : null,
            'tipo_movimiento' => $ultimo->tipo_movimiento,
            'cantidad' => $ultimo->cantidad !== null ? (float) $ultimo->cantidad : null,
            'costo_unitario' => $ultimo->costo_unitario !== null ? (float) $ultimo->costo_unitario : null,
            'saldo_cantidad' => $ultimo->saldo_cantidad !== null ? (float) $ultimo->saldo_cantidad : null,
            'documento' => [
                'id' => $ultimo->documento_id !== null ? (int) $ultimo->documento_id : null,
                'tipo' => $ultimo->documento_tipo ?? null,
                'estado' => $ultimo->documento_estado ?? null,
            ],
        ] : null,
    ]);
}

}
