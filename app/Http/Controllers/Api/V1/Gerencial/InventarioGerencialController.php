<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioGerencialController extends Controller
{
    public function stock(Request $request)
{
    $almacenId = $request->filled('almacen_id') ? (int) $request->almacen_id : null;

    $q = DB::table('inventario_stock as s')
        ->join('productos as p', 'p.id', '=', 's.producto_id')
        ->join('almacenes as a', 'a.id', '=', 's.almacen_id')
        ->select([
            's.id',
            's.almacen_id',
            's.producto_id',
            's.stock_actual',
            's.stock_minimo',
            's.stock_reservado',
            's.costo_promedio',
            's.valor_total',
            'p.nombre as producto_nombre',
            'p.sku as producto_sku',
            'a.nombre as almacen_nombre',
        ])
        ->orderBy('p.nombre');

    if ($almacenId) {
        $q->where('s.almacen_id', $almacenId);
    }

    if ($request->filled('q')) {
        $term = trim($request->q);
        $q->where(function ($x) use ($term) {
            $x->where('p.nombre', 'like', "%{$term}%")
              ->orWhere('p.sku', 'like', "%{$term}%");
        });
    }

    if ($request->filled('con_existencia') && (int)$request->con_existencia === 1) {
        $q->where('s.stock_actual', '>', 0);
    }

    if ($request->filled('minimos') && (int)$request->minimos === 1) {
        $q->whereNotNull('s.stock_minimo')
          ->where('s.stock_minimo', '>', 0)
          ->whereColumn('s.stock_actual', '<=', 's.stock_minimo');
    }

    $perPage = min(max((int) $request->get('per_page', 25), 1), 50);
    $rows = $q->paginate($perPage)->withQueryString();

    return response()->json([
        'ok' => true,
        'data' => collect($rows->items())->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'almacen' => [
                    'id' => (int) $r->almacen_id,
                    'nombre' => $r->almacen_nombre,
                ],
                'producto' => [
                    'id' => (int) $r->producto_id,
                    'nombre' => $r->producto_nombre,
                    'sku' => $r->producto_sku,
                ],
                'stock' => [
                    'stock_actual' => $r->stock_actual !== null ? (float) $r->stock_actual : 0.0,
                    'stock_minimo' => $r->stock_minimo !== null ? (float) $r->stock_minimo : null,
                    'stock_reservado' => $r->stock_reservado !== null ? (float) $r->stock_reservado : null,
                    'costo_promedio' => $r->costo_promedio !== null ? (float) $r->costo_promedio : null,
                    'valor_total' => $r->valor_total !== null ? (float) $r->valor_total : null,
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
public function resumen(Request $request)
{
    $almacenId = $request->filled('almacen_id') ? (int) $request->almacen_id : null;

    $base = DB::table('inventario_stock as s');

    if ($almacenId) {
        $base->where('s.almacen_id', $almacenId);
    }

    // KPIs básicos
    $totales = (clone $base)->selectRaw('
        COUNT(*) as productos_con_registro,
        SUM(CASE WHEN s.stock_actual > 0 THEN 1 ELSE 0 END) as productos_con_existencia,
        SUM(CASE WHEN s.stock_minimo IS NOT NULL AND s.stock_minimo > 0 AND s.stock_actual <= s.stock_minimo THEN 1 ELSE 0 END) as productos_bajo_minimo,
        COALESCE(SUM(s.valor_total),0) as valor_total_inventario,
        COALESCE(SUM(s.stock_actual),0) as stock_total_unidades,
        COALESCE(SUM(s.stock_reservado),0) as stock_total_reservado
    ')->first();

    // Top 10 por valor_total (útil para gerencia)
    $topValor = (clone $base)
        ->join('productos as p', 'p.id', '=', 's.producto_id')
        ->select([
            's.producto_id',
            'p.nombre as producto_nombre',
            'p.sku as producto_sku',
            's.stock_actual',
            's.costo_promedio',
            's.valor_total',
        ])
        ->orderByDesc('s.valor_total')
        ->limit(10)
        ->get()
        ->map(function ($r) {
            return [
                'producto' => [
                    'id' => (int) $r->producto_id,
                    'nombre' => $r->producto_nombre,
                    'sku' => $r->producto_sku,
                ],
                'stock_actual' => $r->stock_actual !== null ? (float) $r->stock_actual : 0.0,
                'costo_promedio' => $r->costo_promedio !== null ? (float) $r->costo_promedio : null,
                'valor_total' => $r->valor_total !== null ? (float) $r->valor_total : 0.0,
            ];
        })
        ->values();

    // Top 10 bajo mínimo (prioridad operativa para gerencia)
    $topMinimos = (clone $base)
        ->join('productos as p', 'p.id', '=', 's.producto_id')
        ->whereNotNull('s.stock_minimo')
        ->where('s.stock_minimo', '>', 0)
        ->whereColumn('s.stock_actual', '<=', 's.stock_minimo')
        ->select([
            's.producto_id',
            'p.nombre as producto_nombre',
            'p.sku as producto_sku',
            's.stock_actual',
            's.stock_minimo',
            's.stock_reservado',
        ])
        ->orderByRaw('(s.stock_minimo - s.stock_actual) DESC')
        ->limit(10)
        ->get()
        ->map(function ($r) {
            return [
                'producto' => [
                    'id' => (int) $r->producto_id,
                    'nombre' => $r->producto_nombre,
                    'sku' => $r->producto_sku,
                ],
                'stock_actual' => $r->stock_actual !== null ? (float) $r->stock_actual : 0.0,
                'stock_minimo' => $r->stock_minimo !== null ? (float) $r->stock_minimo : null,
                'stock_reservado' => $r->stock_reservado !== null ? (float) $r->stock_reservado : null,
                'faltante' => ($r->stock_minimo !== null)
                    ? (float) max(0, ((float)$r->stock_minimo - (float)$r->stock_actual))
                    : null,
            ];
        })
        ->values();

    return response()->json([
        'ok' => true,
        'data' => [
            'filters' => [
                'almacen_id' => $almacenId,
            ],
            'kpis' => [
                'productos_con_registro' => (int) ($totales->productos_con_registro ?? 0),
                'productos_con_existencia' => (int) ($totales->productos_con_existencia ?? 0),
                'productos_bajo_minimo' => (int) ($totales->productos_bajo_minimo ?? 0),
                'valor_total_inventario' => round((float) ($totales->valor_total_inventario ?? 0), 2),
                'stock_total_unidades' => round((float) ($totales->stock_total_unidades ?? 0), 3),
                'stock_total_reservado' => round((float) ($totales->stock_total_reservado ?? 0), 3),
            ],
            'top' => [
                'por_valor' => $topValor,
                'bajo_minimo' => $topMinimos,
            ],
        ],
    ]);
}

}
