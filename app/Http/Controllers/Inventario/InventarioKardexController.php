<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioKardexController extends Controller
{
    public function index(Request $request)
    {
        // Filtros
        $almacenId  = $request->integer('almacen_id');
        $productoId = $request->integer('producto_id');
        $desde      = $request->input('desde'); // YYYY-MM-DD
        $hasta      = $request->input('hasta'); // YYYY-MM-DD

        // Validación suave (web)
        $request->validate([
            'almacen_id'  => ['nullable', 'integer'],
            'producto_id' => ['nullable', 'integer'],
            'desde'       => ['nullable', 'date'],
            'hasta'       => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        // Para selects
        $almacenes = DB::table('almacenes')->orderBy('nombre')->get(['id','nombre']);

        // Query Kardex (inventario_movimientos)
        $q = DB::table('inventario_movimientos as m')
            ->leftJoin('productos as p', 'p.id', '=', 'm.producto_id')
            ->leftJoin('almacenes as a', 'a.id', '=', 'm.almacen_id')
            ->leftJoin('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->select([
                'm.id',
                'm.fecha',
                'm.tipo_movimiento',
                'm.cantidad',
                'm.costo_unitario',
                'm.saldo_cantidad',
                'm.almacen_id',
                'm.producto_id',
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

        if ($productoId) {
            $q->where('m.producto_id', $productoId);
        }

        if ($desde) {
            $q->whereDate('m.fecha', '>=', $desde);
        }

        if ($hasta) {
            $q->whereDate('m.fecha', '<=', $hasta);
        }

        // Recomendación UX: si no filtran por producto/almacén, igual mostrar, pero paginado (ya lo está)
        $movimientos = $q->paginate(30)->withQueryString();

        return view('inventario.kardex.index', compact(
            'movimientos',
            'almacenes',
            'almacenId',
            'productoId',
            'desde',
            'hasta'
        ));
    }
}
