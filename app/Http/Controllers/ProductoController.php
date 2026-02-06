<?php

namespace App\Http\Controllers;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProductoController extends Controller

{

     public function index(Request $request)
{
    $q = Producto::query()
        ->withSum('inventarioStocks as existencias', 'stock_actual')
        ->orderBy('nombre');

    if ($request->filled('estado')) {
        if ($request->estado === 'activos') $q->where('activo', 1);
        if ($request->estado === 'inactivos') $q->where('activo', 0);
    }

    if ($request->filled('q')) {
        $term = trim($request->q);
        $q->where(function ($x) use ($term) {
            $x->where('nombre', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('legacy_prod_id', 'like', "%{$term}%");
        });
    }

    $productos = $q->paginate(20)->withQueryString();

    return view('productos.index', compact('productos'));
}


    public function create()
    {
        $producto = new Producto();
        $producto->tipo = 'PRODUCTO';
        $producto->activo = 1;
        return view('productos.create', compact('producto'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:255'],
            'descripcion' => ['nullable','string','max:500'],
            'sku' => ['nullable','string','max:100','unique:productos,sku'],
            'unidad' => ['nullable','string','max:50'],
            'tipo' => ['nullable','string','max:20'],
            'activo' => ['nullable','boolean'],
        ]);

        $data['activo'] = (bool)($data['activo'] ?? 1);
        $data['tipo'] = $data['tipo'] ?? 'PRODUCTO';


        $producto = Producto::create($data);

        if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'sku' => $producto->sku,
                        'unidad' => $producto->unidad,
                        'tipo' => $producto->tipo,
                    ]
                ]);
            }

        return redirect()
            // ->route('productos.edit', $producto->id)
            ->route('productos.edit', ['producto' => $producto->id, 'tab' => 'general'])

            ->with('success', 'Producto creado.');
    }

    // public function edit(Producto $producto, Request $request)
    //     {
    //         $tab = $request->get('tab', 'general');

    //         // Carga ligera por default
    //         if ($tab === 'proveedores') {
    //             $producto->load(['proveedores' => function ($q) {
    //                 $q->orderBy('nombre');
    //             }]);

    //             $proveedores = Proveedor::where('activo', 1)->orderBy('nombre')->get(['id','nombre','rfc']);
    //             return view('productos.edit', compact('producto', 'tab', 'proveedores'));

                
    //         }

    //         // costos: placeholder por ahora
    //         return view('productos.edit', compact('producto', 'tab'));
    //     }
    public function edit(Producto $producto, Request $request)
{
    $tab = $request->get('tab', 'general',);
  $historialCostos = null;
    if ($tab === 'proveedores') {
        $producto->load(['proveedores' => function ($q) {
            $q->orderBy('nombre');
        }]);

        $proveedores = Proveedor::where('activo', 1)->orderBy('nombre')->get(['id','nombre','rfc']);
        return view('productos.edit', compact('producto', 'tab', 'proveedores'));
    }
    if ($tab === 'costos'){
        $historialCostos = DB::table('producto_proveedor_precios as h')
            ->join('proveedores as pr', 'pr.id', '=', 'h.proveedor_id')
            ->where('h.producto_id', $producto->id)
            ->orderByDesc('h.created_at')
            ->select([
                'h.id',
                'h.created_at',
                'h.precio',
                'h.moneda',
                'h.orden_compra_id',
                'pr.id as proveedor_id',
                'pr.nombre as proveedor_nombre',
                'pr.rfc as proveedor_rfc',
            ])
            ->paginate(25, ['*'], 'hist'); 
    }

    if ($tab === 'kardex') {
        $almacenId = $request->integer('almacen_id');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        $request->validate([
            'almacen_id' => ['nullable','integer'],
            'desde' => ['nullable','date'],
            'hasta' => ['nullable','date','after_or_equal:desde'],
        ]);

        $almacenes = DB::table('almacenes')->orderBy('nombre')->get(['id','nombre']);

        $q = DB::table('inventario_movimientos as m')
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
                'm.documento_id',
                'a.nombre as almacen_nombre',
                'd.tipo as documento_tipo',
                'd.estado as documento_estado',
            ])
            ->where('m.producto_id', $producto->id)
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

        $movimientos = $q->paginate(30)->withQueryString();

        return view('productos.edit', compact(
            'producto','tab','movimientos','almacenes','almacenId','desde','hasta',
        ));
    }

    // costos: placeholder por ahora
    return view('productos.edit', compact('producto', 'tab','historialCostos'));
}

    public function proveedoresAttach(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'proveedor_id' => ['required','exists:proveedores,id'],
            'precio_lista' => ['required','numeric','min:0'],
            'moneda' => ['required','string','max:10'],
            'tiempo_entrega_dias' => ['nullable','integer','min:0'],
            'activo' => ['nullable','boolean'],
            'notas' => ['nullable','string','max:255'],
        ]);

        $data['activo'] = (int)($data['activo'] ?? 1);

        DB::transaction(function () use ($producto, $data) {
            // si ya existe relación, actualiza pivot; si no, attach
            $producto->proveedores()->syncWithoutDetaching([
                $data['proveedor_id'] => [
                    'precio_lista' => $data['precio_lista'],
                    'moneda' => $data['moneda'],
                    'tiempo_entrega_dias' => $data['tiempo_entrega_dias'] ?? null,
                    'activo' => $data['activo'],
                    'notas' => $data['notas'] ?? null,
                    'updated_at' => now(),
                ]
            ]);
        });

        return back()->with('success', 'Proveedor asignado / actualizado para este producto.');
    }

    // public function proveedoresUpdate(Request $request, Producto $producto, Proveedor $proveedor)
    // {
    //     $data = $request->validate([
    //         'precio_lista' => ['required','numeric','min:0'],
    //         'moneda' => ['required','string','max:10'],
    //         'tiempo_entrega_dias' => ['nullable','integer','min:0'],
    //         'activo' => ['nullable','boolean'],
    //         'notas' => ['nullable','string','max:255'],
    //     ]);

    //     // $data['activo'] = (int)($data['activo'] ?? 0);
    //     $data['activo'] = $request->has('activo') ? 1 : 0;


    //     $producto->proveedores()->updateExistingPivot($proveedor->id, [
    //         'precio_lista' => $data['precio_lista'],
    //         'moneda' => $data['moneda'],
    //         'tiempo_entrega_dias' => $data['tiempo_entrega_dias'] ?? null,
    //         'activo' => $data['activo'],
    //         'notas' => $data['notas'] ?? null,
    //         'updated_at' => now(),
    //     ]);

    //     return back()->with('success', 'Relación producto–proveedor actualizada.');
    // }


public function proveedoresUpdate(Request $request, Producto $producto, Proveedor $proveedor)
{


    $data = $request->validate([
        'precio_lista' => ['required','numeric','min:0'],
        'moneda' => ['required','string','max:10'],
        'tiempo_entrega_dias' => ['nullable','integer','min:0'],
        'activo' => ['nullable','boolean'],
        'notas' => ['nullable','string','max:255'],
    ]);

    // OJO: no mates "activo" por default si el form no lo manda
    // Si es checkbox:
    $activo = $request->has('activo') ? 1 : 0;

    DB::transaction(function () use ($producto, $proveedor, $data, $activo) {

        // 1) leer pivote actual (si existe)
        $pivot = DB::table('producto_proveedor')
            ->where('producto_id', $producto->id)
            ->where('proveedor_id', $proveedor->id)
            ->first();

        $nuevoPrecio = (float) $data['precio_lista'];
        $nuevaMoneda = (string) $data['moneda'];

        // 2) detectar cambio de precio/moneda
        $cambioPrecio = !$pivot
            || (float)$pivot->precio_lista !== $nuevoPrecio
            || (string)$pivot->moneda !== $nuevaMoneda;

        // 3) si cambió, insertar histórico
        if ($cambioPrecio) {
            DB::table('producto_proveedor_precios')->insert([
                'producto_id' => $producto->id,
                'proveedor_id'=> $proveedor->id,
                'precio'      => $nuevoPrecio,
                'moneda'      => $nuevaMoneda,
                'orden_compra_id' => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 4) upsert pivote actual (NO se borra)
        DB::table('producto_proveedor')->updateOrInsert(
            ['producto_id' => $producto->id, 'proveedor_id' => $proveedor->id],
            [
                'precio_lista' => $nuevoPrecio,
                'moneda' => $nuevaMoneda,
                'tiempo_entrega_dias' => $data['tiempo_entrega_dias'] ?? null,
                'activo' => $activo,
                'notas' => $data['notas'] ?? null,
                'updated_at' => now(),
                'created_at' => $pivot ? $pivot->created_at : now(),
            ]
        );
    });
// \Log::info('updateExistingPivot result', ['updated' => $updated]);

    return back()->with('success', 'Relación producto–proveedor actualizada.');
}

    public function proveedoresDetach(Producto $producto, Proveedor $proveedor)
    {
        $producto->proveedores()->detach($proveedor->id);
        return back()->with('success', 'Proveedor removido de este producto.');
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:255'],
            'descripcion' => ['nullable','string','max:500'],
            'sku' => ['nullable','string','max:100','unique:productos,sku,' . $producto->id],
            'unidad' => ['nullable','string','max:50'],
            'tipo' => ['nullable','string','max:20'],
            'activo' => ['nullable','boolean'],
        ]);

        $data['activo'] = (bool)($data['activo'] ?? 0);

        $producto->update($data);

        return back()->with('success', 'Producto actualizado.');
    }

    public function toggleActivo(Producto $producto)
    {
        $producto->activo = !$producto->activo;
        $producto->save();

        return back()->with('success', 'Estatus actualizado.');
    }
    //
    public function buscar(Request $request)
        {
            $term = trim((string) $request->get('q', ''));

            if (mb_strlen($term) < 2) {
                return response()->json([]);
            }

            $productos = Producto::query()
                ->where('activo', 1)
                ->where(function ($q) use ($term) {
                    $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('legacy_prod_id', 'like', "%{$term}%");
                })
                ->orderBy('nombre')
                ->limit(15)
                ->get(['id','legacy_prod_id','nombre','unidad','sku']);

            return response()->json($productos->map(fn($p) => [
                'id' => $p->id,
                'legacy_prod_id' => $p->legacy_prod_id,
                'nombre' => $p->nombre,
                'unidad' => $p->unidad,
                'sku' => $p->sku,
            ]));
        }
}
