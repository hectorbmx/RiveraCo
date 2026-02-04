<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Almacen;
use App\Models\InventarioStock;
use Illuminate\Support\Facades\DB;

class InventarioStockController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'almacen_id' => ['nullable','integer'],
            'q'          => ['nullable','string','max:120'],
            'minimos'    => ['nullable','boolean'],
        ]);

        $almacenId = $data['almacen_id'] ?? 1; // por ahora default
        $q         = $data['q'] ?? null;
        $minimos   = (bool)($data['minimos'] ?? false);

        $query = DB::table('inventario_stock as s')
            ->join('productos as p', 'p.id', '=', 's.producto_id')
            ->select([
                's.almacen_id',
                's.producto_id',
                'p.nombre',
                'p.sku',
                'p.unidad',
                'p.tipo_inventario',
                'p.stock_minimo',
                'p.punto_reorden',
                's.stock_actual',
                's.valor_total',
                's.costo_promedio',
            ])
            ->where('s.almacen_id', $almacenId);

        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('p.nombre', 'like', "%{$q}%")
                   ->orWhere('p.sku', 'like', "%{$q}%");
            });
        }

        if ($minimos) {
            $query->whereRaw('s.stock_actual <= GREATEST(p.stock_minimo, p.punto_reorden)');
        }

        $rows = $query->orderBy('p.nombre')->paginate(50)->withQueryString();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }

     public function view(Request $request)
    {
        $almacenId = $request->integer('almacen_id');
        $q         = trim((string) $request->get('q', ''));
        $minimos   = (int) $request->get('minimos', 0);

        $query = InventarioStock::query()
            ->with(['almacen'])
            ->when($almacenId, fn($qq) => $qq->where('almacen_id', $almacenId))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('sku', 'like', "%{$q}%")
                      ->orWhere('descripcion', 'like', "%{$q}%");
                });
            })
            ->when($minimos === 1, fn($qq) => $qq->whereColumn('existencia', '<=', 'stock_minimo'))
            ->orderBy('id');
        $base = (clone $query)->getQuery();
       $totalesPorAlmacen = DB::table('inventario_stock')
                ->selectRaw('almacen_id, COALESCE(SUM(stock_actual),0) as stock_total, COALESCE(SUM(valor_total),0) as valor_total')
                ->when($almacenId, fn($qq) => $qq->where('almacen_id', $almacenId))
                ->when($q !== '', function ($qq) use ($q) {
                    // ⚠️ si NO tienes sku/descripcion en inventario_stock, no busques aquí
                    // Por ahora, solo dejamos el filtro por producto_id si el usuario teclea número
                    if (ctype_digit($q)) {
                        $qq->where('producto_id', (int) $q);
                    }
                })
                ->when($minimos === 1, function ($qq) {
                    // si no tienes stock_minimo en esta tabla, este filtro NO aplica aún
                    // lo dejamos sin tocar para no romper
                })
                ->groupBy('almacen_id')
                ->get()
                ->keyBy('almacen_id');


        $stocks = $query->paginate(25)->withQueryString();

        $almacenes = Almacen::query()->orderBy('nombre')->get(['id','nombre']);

        return view('inventario.stock.index', compact('stocks','almacenes','almacenId','q','minimos','totalesPorAlmacen'));
    }
}
