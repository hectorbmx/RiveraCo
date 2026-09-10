<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\HuentitanFormula;
use App\Models\HuentitanFormulaMaterial;
use App\Models\HuentitanOrdenFabricacion;
use App\Models\HuentitanEntrada;
use App\Models\HuentitanOrdenFabricacionMaterial;
use App\Models\HuentitanSalida;
use App\Models\InventarioCorte;
use App\Models\InventarioCorteDetalle;
use App\Models\InventarioStock;
use App\Models\Producto;
use App\Services\Inventario\HuentitanInventoryImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HuentitanInventarioController extends Controller
{
    private const ALMACEN_CODIGO = 'AL-HUENTITAN';

    public function dashboard()
    {
        $almacen = $this->almacenHuentitan();

        $resumen = [
            'productos' => InventarioStock::query()->where('almacen_id', $almacen->id)->count(),
            'con_existencia' => InventarioStock::query()->where('almacen_id', $almacen->id)->where('stock_actual', '>', 0)->count(),
            'valor_total' => InventarioStock::query()->where('almacen_id', $almacen->id)->sum('valor_total'),
            'bajo_minimo' => DB::table('inventario_stock as stock')
                ->join('productos as productos', 'productos.id', '=', 'stock.producto_id')
                ->where('stock.almacen_id', $almacen->id)
                ->where('productos.stock_minimo', '>', 0)
                ->whereColumn('stock.stock_actual', '<=', 'productos.stock_minimo')
                ->count(),
        ];

        $ultimoCorte = InventarioCorte::query()
            ->where('almacen_id', $almacen->id)
            ->orderByDesc('fecha_hasta')
            ->orderByDesc('id')
            ->first();

        return view('huentitan.index', compact('almacen', 'resumen', 'ultimoCorte'));
    }


    public function empleados(Request $request)
    {
        $almacen = $this->almacenHuentitan();
        $areaHuentitan = $this->areaHuentitan($almacen);

        $estatus = $request->query('estatus', 'activo');
        $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';
        $busqueda = trim((string) $request->query('q', ''));

        $empleados = collect();

        if ($areaHuentitan) {
            $empleados = Empleado::query()
                ->with('areaRef')
                ->where('Area', $areaHuentitan->id)
                ->when($estatus === 'activo', fn ($query) => $query->where('Estatus', 1))
                ->when($estatus === 'baja', fn ($query) => $query->where('Estatus', 2))
                ->when($busqueda !== '', function ($query) use ($busqueda) {
                    $query->where(function ($empleado) use ($busqueda) {
                        $empleado->where('Nombre', 'like', '%' . $busqueda . '%')
                            ->orWhere('Apellidos', 'like', '%' . $busqueda . '%')
                            ->orWhere('Puesto', 'like', '%' . $busqueda . '%')
                            ->orWhere('id_Empleado', 'like', '%' . $busqueda . '%');
                    });
                })
                ->orderBy('Nombre')
                ->orderBy('Apellidos')
                ->get();
        }

        $resumenEmpleados = [
            'total' => $empleados->count(),
            'activos' => $empleados->where('Estatus', 1)->count(),
            'baja' => $empleados->where('Estatus', 2)->count(),
        ];

        return view('huentitan.empleados', compact('almacen', 'areaHuentitan', 'empleados', 'resumenEmpleados', 'estatus', 'busqueda'));
    }

    public function productos(Request $request)
    {
        $almacen = $this->almacenHuentitan();

        $busqueda = trim((string) $request->query('q', ''));
        $tipo = $request->query('tipo');
        $formula = $request->query('formula');
        $stock = $request->query('stock');

        $tiposPermitidos = ['materia_prima', 'producto_terminado', 'subensamble'];
        $formula = in_array($formula, ['si', 'no'], true) ? $formula : null;
        $stock = $stock === 'bajo_minimo' ? $stock : null;
        $tipo = in_array($tipo, $tiposPermitidos, true) ? $tipo : null;

        $productos = Producto::query()
            ->with(['inventarioStocks' => fn ($query) => $query->where('almacen_id', $almacen->id)])
            ->where('sku', 'like', 'HUE-%')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($producto) use ($busqueda) {
                    $producto->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('sku', 'like', '%' . $busqueda . '%')
                        ->orWhere('descripcion', 'like', '%' . $busqueda . '%');
                });
            })
            ->when($tipo, fn ($query) => $query->where('tipo_inventario', $tipo))
            ->when($formula === 'si', fn ($query) => $query->where('requiere_formula', true))
            ->when($formula === 'no', fn ($query) => $query->where(function ($producto) {
                $producto->where('requiere_formula', false)->orWhereNull('requiere_formula');
            }))
            ->when($stock === 'bajo_minimo', function ($query) use ($almacen) {
                $query->where('stock_minimo', '>', 0)
                    ->whereHas('inventarioStocks', function ($stockQuery) use ($almacen) {
                        $stockQuery->where('almacen_id', $almacen->id)
                            ->whereColumn('inventario_stock.stock_actual', '<=', 'productos.stock_minimo');
                    });
            })
            ->orderBy('sku')
            ->paginate(25)
            ->withQueryString();

        $productoIds = $productos->getCollection()->pluck('id');

        $ultimosMovimientos = $productoIds->isEmpty()
            ? collect()
            : DB::table('inventario_movimientos as m')
                ->where('m.almacen_id', $almacen->id)
                ->whereIn('m.producto_id', $productoIds)
                ->select([
                    'm.id',
                    'm.producto_id',
                    'm.documento_id',
                    'm.fecha',
                    'm.tipo_movimiento',
                    'm.cantidad',
                    'm.costo_unitario',
                    'm.saldo_cantidad',
                ])
                ->orderByDesc('m.fecha')
                ->orderByDesc('m.id')
                ->get()
                ->unique('producto_id')
                ->keyBy('producto_id');

        $documentosUltimosMovimientos = $this->resolverDocumentosMovimientosHuentitan($ultimosMovimientos);

        $resumenProductos = [
            'total' => Producto::query()->where('sku', 'like', 'HUE-%')->count(),
            'materia_prima' => Producto::query()->where('sku', 'like', 'HUE-%')->where('tipo_inventario', 'materia_prima')->count(),
            'producto_terminado' => Producto::query()->where('sku', 'like', 'HUE-%')->where('tipo_inventario', 'producto_terminado')->count(),
            'subensamble' => Producto::query()->where('sku', 'like', 'HUE-%')->where('tipo_inventario', 'subensamble')->count(),
            'requieren_formula' => Producto::query()->where('sku', 'like', 'HUE-%')->where('requiere_formula', true)->count(),
        ];

        return view('huentitan.productos.index', compact(
            'almacen',
            'productos',
            'ultimosMovimientos',
            'documentosUltimosMovimientos',
            'resumenProductos',
            'busqueda',
            'tipo',
            'formula',
            'stock'
        ));
    }
    private function resolverDocumentosMovimientosHuentitan($movimientos)
    {
        $movimientos = collect($movimientos)->filter(fn ($movimiento) => $movimiento && $movimiento->documento_id);

        if ($movimientos->isEmpty()) {
            return collect();
        }

        $entradas = HuentitanEntrada::query()
            ->whereIn('id', $movimientos->where('tipo_movimiento', 'in')->pluck('documento_id')->filter()->unique()->values())
            ->get(['id', 'folio', 'estado'])
            ->keyBy('id');

        $salidas = HuentitanSalida::query()
            ->with('obra')
            ->whereIn('id', $movimientos->where('tipo_movimiento', 'out')->pluck('documento_id')->filter()->unique()->values())
            ->get(['id', 'folio', 'estado', 'obra_id'])
            ->keyBy('id');

        return $movimientos->mapWithKeys(function ($movimiento) use ($entradas, $salidas) {
            if ($movimiento->tipo_movimiento === 'in') {
                $entrada = $entradas->get((int) $movimiento->documento_id);

                if ($entrada) {
                    return [(int) $movimiento->id => [
                        'tipo' => 'Entrada HUENTITAN',
                        'folio' => $entrada->folio,
                        'estado' => $entrada->estado,
                        'route' => route('huentitan.entradas.show', $entrada->id),
                        'obra' => null,
                    ]];
                }
            }

            if ($movimiento->tipo_movimiento === 'out') {
                $salida = $salidas->get((int) $movimiento->documento_id);

                if ($salida) {
                    $obra = $salida->obra
                        ? trim(($salida->obra->clave_obra ? $salida->obra->clave_obra . ' - ' : '') . $salida->obra->nombre)
                        : null;

                    return [(int) $movimiento->id => [
                        'tipo' => 'Salida a obra',
                        'folio' => $salida->folio,
                        'estado' => $salida->estado,
                        'route' => route('huentitan.salidas.show', $salida->id),
                        'obra' => $obra,
                    ]];
                }
            }

            return [(int) $movimiento->id => [
                'tipo' => $movimiento->documento_tipo ?? 'Documento inventario',
                'folio' => $movimiento->documento_id ? '#' . $movimiento->documento_id : null,
                'estado' => $movimiento->documento_estado ?? null,
                'route' => $movimiento->documento_id ? route('inventario.documentos.show', $movimiento->documento_id) : null,
                'obra' => null,
            ]];
        });
    }
    public function actualizarProductoGeneral(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'tipo_inventario' => ['required', 'in:materia_prima,producto_terminado,subensamble'],
            'origen_abastecimiento' => ['required', 'in:compra,fabricacion,ambos'],
            'requiere_formula' => ['nullable', 'boolean'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $producto->update([
            'tipo_inventario' => $data['tipo_inventario'],
            'origen_abastecimiento' => $data['origen_abastecimiento'],
            'requiere_formula' => $request->boolean('requiere_formula'),
            'stock_minimo' => $data['stock_minimo'] ?? 0,
        ]);

        return redirect()
            ->route('huentitan.productos.show', [
                'producto' => $producto->id,
                'tab' => $request->boolean('requiere_formula') ? 'formula' : 'resumen',
            ])
            ->with('status', 'Configuracion del producto actualizada.');
    }

    public function actualizarProductoEspecificaciones(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'diametro' => ['nullable', 'string', 'max:100'],
            'largo' => ['nullable', 'string', 'max:100'],
            'ancho' => ['nullable', 'string', 'max:100'],
            'alto' => ['nullable', 'string', 'max:100'],
            'espesor_calibre' => ['nullable', 'string', 'max:100'],
            'peso' => ['nullable', 'string', 'max:100'],
            'material_base' => ['nullable', 'string', 'max:160'],
            'acabado' => ['nullable', 'string', 'max:160'],
            'norma' => ['nullable', 'string', 'max:160'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $producto->update([
            'especificaciones_tecnicas' => collect($data)
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->all(),
        ]);

        return redirect()
            ->route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'especificaciones'])
            ->with('status', 'Especificaciones tecnicas actualizadas.');
    }

    public function buscarMaterialesFormula(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $materiales = Producto::query()
            ->where('sku', 'like', 'HUE-%')
            ->where('id', '<>', $producto->id)
            ->where('activo', true)
            // Por ahora las formulas no consumen productos fabricados internamente.
            // Si despues se habilitan subensambles, esta regla debe revisarse.
            ->where(function ($query) {
                $query->where('requiere_formula', false)->orWhereNull('requiere_formula');
            })
            ->where(function ($query) use ($term) {
                $query->where('nombre', 'like', '%' . $term . '%')
                    ->orWhere('sku', 'like', '%' . $term . '%')
                    ->orWhere('descripcion', 'like', '%' . $term . '%');
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'sku', 'nombre', 'unidad']);

        return response()->json($materiales->map(fn ($material) => [
            'id' => $material->id,
            'sku' => $material->sku,
            'nombre' => $material->nombre,
            'unidad' => $material->unidad,
            'label' => trim(($material->sku ? $material->sku . ' - ' : '') . $material->nombre),
        ]));
    }

    public function actualizarProductoFormula(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'cantidad_base' => ['required', 'numeric', 'gt:0'],
            'unidad_base' => ['nullable', 'string', 'max:50'],
            'merma_esperada_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tiempo_estimado_minutos' => ['nullable', 'integer', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $producto->update(['requiere_formula' => true]);

        HuentitanFormula::updateOrCreate(
            ['producto_id' => $producto->id],
            [
                'cantidad_base' => $data['cantidad_base'],
                'unidad_base' => $data['unidad_base'] ?: $producto->unidad,
                'merma_esperada_porcentaje' => $data['merma_esperada_porcentaje'] ?? 0,
                'tiempo_estimado_minutos' => $data['tiempo_estimado_minutos'] ?? null,
                'notas' => $data['notas'] ?? null,
            ]
        );

        return redirect()
            ->route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'formula'])
            ->with('status', 'Formula actualizada.');
    }

    public function agregarProductoFormulaMaterial(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'material_producto_id' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'unidad' => ['nullable', 'string', 'max:50'],
            'merma_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        $material = Producto::query()
            ->where('id', $data['material_producto_id'])
            ->where('sku', 'like', 'HUE-%')
            ->where('activo', true)
            // Por ahora las formulas no consumen productos fabricados internamente.
            // Si despues se habilitan subensambles, esta regla debe revisarse.
            ->where(function ($query) {
                $query->where('requiere_formula', false)->orWhereNull('requiere_formula');
            })
            ->firstOrFail();

        abort_if($material->id === $producto->id, 422, 'El producto terminado no puede consumirse a si mismo.');

        $producto->update(['requiere_formula' => true]);

        $formula = HuentitanFormula::firstOrCreate(
            ['producto_id' => $producto->id],
            [
                'cantidad_base' => 1,
                'unidad_base' => $producto->unidad,
                'merma_esperada_porcentaje' => 0,
            ]
        );

        HuentitanFormulaMaterial::updateOrCreate(
            [
                'formula_id' => $formula->id,
                'material_producto_id' => $material->id,
            ],
            [
                'cantidad' => $data['cantidad'],
                'unidad' => $data['unidad'] ?: $material->unidad,
                'merma_porcentaje' => $data['merma_porcentaje'] ?? 0,
                'notas' => $data['notas'] ?? null,
            ]
        );

        return redirect()
            ->route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'formula'])
            ->with('status', 'Material agregado a la formula.');
    }

    public function eliminarProductoFormulaMaterial(Producto $producto, HuentitanFormulaMaterial $material)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);
        abort_unless($material->formula && (int) $material->formula->producto_id === (int) $producto->id, 404);

        $material->delete();

        return redirect()
            ->route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'formula'])
            ->with('status', 'Material eliminado de la formula.');
    }

    public function productoDetalle(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $almacen = $this->almacenHuentitan();
        $tab = $request->query('tab', 'resumen');
        $tabsPermitidos = ['resumen', 'inventario', 'especificaciones', 'kardex', 'formula', 'proveedores', 'costos'];
        $tab = in_array($tab, $tabsPermitidos, true) ? $tab : 'resumen';
        if ($tab === 'formula' && ! $producto->requiere_formula) {
            $tab = 'resumen';
        }

        $producto->load(['proveedores' => function ($query) {
            $query->orderBy('nombre');
        }]);

        $formulaProducto = $producto->huentitanFormula()
            ->with(['materiales.material'])
            ->first();

        $materialesDisponibles = Producto::query()
            ->where('sku', 'like', 'HUE-%')
            ->where('id', '<>', $producto->id)
            ->where('activo', true)
            // Por ahora las formulas no consumen productos fabricados internamente.
            // Si despues se habilitan subensambles, esta regla debe revisarse.
            ->where(function ($query) {
                $query->where('requiere_formula', false)->orWhereNull('requiere_formula');
            })
            ->orderBy('nombre')
            ->get(['id', 'sku', 'nombre', 'unidad']);

        $materialStockMap = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->whereIn('producto_id', $materialesDisponibles->pluck('id'))
            ->get()
            ->keyBy('producto_id');

        $costoFormulaEstimado = $formulaProducto
            ? $formulaProducto->materiales->sum(function ($materialFormula) use ($materialStockMap) {
                $stock = $materialStockMap->get($materialFormula->material_producto_id);
                $cantidad = (float) $materialFormula->cantidad;
                $merma = (float) $materialFormula->merma_porcentaje;

                return $cantidad * (1 + ($merma / 100)) * (float) ($stock->costo_promedio ?? 0);
            })
            : 0;

        $costoFormulaUnitario = $formulaProducto && (float) $formulaProducto->cantidad_base > 0
            ? $costoFormulaEstimado / (float) $formulaProducto->cantidad_base
            : $costoFormulaEstimado;

        $stockActual = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->where('producto_id', $producto->id)
            ->first();

        $ultimoDetalleCorte = InventarioCorteDetalle::query()
            ->where('producto_id', $producto->id)
            ->whereHas('corte', fn ($query) => $query->where('almacen_id', $almacen->id))
            ->with('corte')
            ->orderByDesc(
                InventarioCorte::query()
                    ->select('fecha_hasta')
                    ->whereColumn('inventario_cortes.id', 'inventario_corte_detalles.corte_id')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->first();

        $movimientos = DB::table('inventario_movimientos as m')
            ->leftJoin('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->where('m.almacen_id', $almacen->id)
            ->where('m.producto_id', $producto->id)
            ->select([
                'm.id',
                'm.fecha',
                'm.tipo_movimiento',
                'm.cantidad',
                'm.costo_unitario',
                'm.saldo_cantidad',
                'm.documento_id',
                'd.tipo as documento_tipo',
                'd.estado as documento_estado',
            ])
            ->orderByDesc('m.fecha')
            ->orderByDesc('m.id')
            ->paginate(20, ['*'], 'mov')
            ->withQueryString();

        $ultimoMovimiento = DB::table('inventario_movimientos as m')
            ->leftJoin('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->where('m.almacen_id', $almacen->id)
            ->where('m.producto_id', $producto->id)
            ->select([
                'm.id',
                'm.fecha',
                'm.tipo_movimiento',
                'm.cantidad',
                'm.costo_unitario',
                'm.saldo_cantidad',
                'm.documento_id',
                'd.tipo as documento_tipo',
                'd.estado as documento_estado',
            ])
            ->orderByDesc('m.fecha')
            ->orderByDesc('m.id')
            ->first();

        $historialCostos = collect();
        if (DB::getSchemaBuilder()->hasTable('producto_proveedor_precios')) {
            $historialCostos = DB::table('producto_proveedor_precios as h')
                ->join('proveedores as p', 'p.id', '=', 'h.proveedor_id')
                ->where('h.producto_id', $producto->id)
                ->orderByDesc('h.created_at')
                ->select([
                    'h.created_at',
                    'h.precio',
                    'h.moneda',
                    'h.orden_compra_id',
                    'p.nombre as proveedor_nombre',
                ])
                ->limit(20)
                ->get();
        }

        $resumen = [
            'stock_actual' => (float) ($stockActual->stock_actual ?? 0),
            'stock_reservado' => (float) ($stockActual->stock_reservado ?? 0),
            'stock_disponible' => max(0, (float) ($stockActual->stock_actual ?? 0) - (float) ($stockActual->stock_reservado ?? 0)),
            'valor_total' => (float) ($stockActual->valor_total ?? 0),
            'costo_promedio' => (float) ($stockActual->costo_promedio ?? 0),
            'stock_minimo' => (float) ($producto->stock_minimo ?? 0),
            'proveedores' => $producto->proveedores->count(),
            'movimientos' => $movimientos->total(),
        ];

        return view('huentitan.productos.show', compact(
            'almacen',
            'producto',
            'tab',
            'stockActual',
            'ultimoDetalleCorte',
            'movimientos',
            'historialCostos',
            'ultimoMovimiento',
            'formulaProducto',
            'materialesDisponibles',
            'materialStockMap',
            'costoFormulaEstimado',
            'costoFormulaUnitario',
            'resumen'
        ));
    }

    public function ordenesCompra()
    {
        return redirect()->route('ordenes_compra.index', [
            'area_codigo' => 'HT',
        ]);
    }

    public function ordenesFabricacion()
    {
        $almacen = $this->almacenHuentitan();

        $ordenes = HuentitanOrdenFabricacion::query()
            ->with(['producto', 'creador'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'total' => HuentitanOrdenFabricacion::query()->count(),
            'borrador' => HuentitanOrdenFabricacion::query()->where('estado', 'borrador')->count(),
            'calculada' => HuentitanOrdenFabricacion::query()->where('estado', 'calculada')->count(),
            'pendiente_material' => HuentitanOrdenFabricacion::query()->where('estado', 'pendiente_material')->count(),
            'lista_para_apartar' => HuentitanOrdenFabricacion::query()->where('estado', 'lista_para_apartar')->count(),
            'apartada' => HuentitanOrdenFabricacion::query()->where('estado', 'apartada')->count(),
            'autorizada' => HuentitanOrdenFabricacion::query()->where('estado', 'autorizada')->count(),
            'en_produccion' => HuentitanOrdenFabricacion::query()->where('estado', 'en_produccion')->count(),
            'cerrada' => HuentitanOrdenFabricacion::query()->where('estado', 'cerrada')->count(),
            'cancelada' => HuentitanOrdenFabricacion::query()->where('estado', 'cancelada')->count(),
        ];

        $estados = HuentitanOrdenFabricacion::ESTADOS;

        return view('huentitan.ordenes_fabricacion.index', compact('almacen', 'ordenes', 'resumen', 'estados'));
    }

    public function crearOrdenFabricacion()
    {
        $almacen = $this->almacenHuentitan();

        $productosFabricables = Producto::query()
            ->with(['huentitanFormula.materiales.material'])
            ->where('sku', 'like', 'HUE-%')
            ->where('requiere_formula', true)
            ->whereHas('huentitanFormula.materiales')
            ->orderBy('nombre')
            ->get(['id', 'sku', 'nombre', 'unidad']);

        $materialIds = $productosFabricables
            ->flatMap(fn ($producto) => $producto->huentitanFormula?->materiales->pluck('material_producto_id') ?? collect())
            ->filter()
            ->unique()
            ->values();

        $stockMap = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->whereIn('producto_id', $materialIds)
            ->get()
            ->keyBy('producto_id');

        $vistaPreviaFormulas = $productosFabricables->mapWithKeys(function ($producto) use ($stockMap) {
            $formula = $producto->huentitanFormula;

            return [$producto->id => [
                'producto' => [
                    'id' => $producto->id,
                    'sku' => $producto->sku,
                    'nombre' => $producto->nombre,
                    'unidad' => $producto->unidad,
                ],
                'formula' => [
                    'cantidad_base' => (float) ($formula->cantidad_base ?? 1),
                    'unidad_base' => $formula->unidad_base ?? $producto->unidad,
                    'merma_esperada_porcentaje' => (float) ($formula->merma_esperada_porcentaje ?? 0),
                    'tiempo_estimado_minutos' => $formula->tiempo_estimado_minutos,
                ],
                'materiales' => $formula?->materiales->map(function ($materialFormula) use ($stockMap) {
                    $material = $materialFormula->material;
                    $stock = $stockMap->get($materialFormula->material_producto_id);
                    $stockActual = (float) ($stock->stock_actual ?? 0);
                    $stockReservado = (float) ($stock->stock_reservado ?? 0);
                    $stockDisponible = max(0, $stockActual - $stockReservado);

                    return [
                        'id' => $materialFormula->id,
                        'material_producto_id' => $material?->id,
                        'sku' => $material?->sku,
                        'nombre' => $material?->nombre ?? 'Material no disponible',
                        'cantidad_por_unidad' => (float) $materialFormula->cantidad,
                        'unidad' => $materialFormula->unidad ?: $material?->unidad,
                        'merma_porcentaje' => (float) $materialFormula->merma_porcentaje,
                        'stock_actual' => $stockActual,
                        'stock_reservado' => $stockReservado,
                        'stock_disponible' => $stockDisponible,
                        'costo_unitario' => (float) ($stock->costo_promedio ?? 0),
                    ];
                })->values() ?? collect(),
            ]];
        });

        $estados = HuentitanOrdenFabricacion::ESTADOS;

        return view('huentitan.ordenes_fabricacion.create', compact('almacen', 'productosFabricables', 'vistaPreviaFormulas', 'estados'));
    }

    public function verOrdenFabricacion(HuentitanOrdenFabricacion $orden)
    {
        $almacen = $this->almacenHuentitan();

        $orden->load(['producto', 'creador', 'calculador', 'apartador', 'iniciadorProduccion', 'materiales.material', 'materiales.compraMarcadaPor']);

        $stockMap = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->whereIn('producto_id', $orden->materiales->pluck('material_producto_id')->filter())
            ->get()
            ->keyBy('producto_id');

        $materiales = $orden->materiales->map(function ($material) use ($stockMap) {
            $stock = $stockMap->get($material->material_producto_id);
            $stockActual = (float) ($stock->stock_actual ?? 0);
            $stockReservado = (float) ($stock->stock_reservado ?? 0);
            $stockDisponible = max(0, $stockActual - $stockReservado);
            $cantidadRequerida = (float) $material->cantidad_requerida;
            $faltante = max(0, $cantidadRequerida - $stockDisponible);

            return [
                'snapshot' => $material,
                'stock_actual' => $stockActual,
                'stock_reservado' => $stockReservado,
                'stock_disponible' => $stockDisponible,
                'faltante' => $faltante,
            ];
        });

        $resumenMateriales = [
            'materiales' => $materiales->count(),
            'con_faltante' => in_array($orden->estado, ['calculada', 'pendiente_material', 'lista_para_apartar', 'apartada'], true)
                ? $orden->materiales->filter(fn ($material) => (float) $material->faltante_calculado > 0)->count()
                : $materiales->where('faltante', '>', 0)->count(),
            'requieren_compra' => $orden->materiales->where('requiere_compra', true)->count(),
            'costo_estimado' => (float) $orden->costo_material_estimado,
        ];

        $estados = HuentitanOrdenFabricacion::ESTADOS;

        return view('huentitan.ordenes_fabricacion.show', compact('almacen', 'orden', 'materiales', 'resumenMateriales', 'estados'));
    }
    public function calcularOrdenFabricacion(HuentitanOrdenFabricacion $orden)
    {
        abort_if(in_array($orden->estado, ['en_produccion', 'cerrada', 'cancelada'], true), 422, 'La orden ya no puede recalcularse.');

        $almacen = $this->almacenHuentitan();

        DB::transaction(function () use ($almacen, $orden) {
            $this->revisarDisponibilidadOrdenFabricacion($orden, $almacen);
        });

        return redirect()
            ->route('huentitan.ordenes-fabricacion.show', $orden)
            ->with('status', 'Disponibilidad revisada para la orden ' . $orden->folio . '.');
    }

    private function revisarDisponibilidadOrdenFabricacion(HuentitanOrdenFabricacion $orden, Almacen $almacen): array
    {
        $orden->load('materiales');

        $stockMap = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->whereIn('producto_id', $orden->materiales->pluck('material_producto_id')->filter())
            ->lockForUpdate()
            ->get()
            ->keyBy('producto_id');

        $faltantes = 0;

        foreach ($orden->materiales as $material) {
            $stock = $stockMap->get($material->material_producto_id);
            $stockActual = (float) ($stock->stock_actual ?? 0);
            $stockReservado = (float) ($stock->stock_reservado ?? 0);
            $stockDisponible = max(0, $stockActual - $stockReservado);
            $faltante = max(0, (float) $material->cantidad_requerida - $stockDisponible);
            $faltantes += $faltante > 0 ? 1 : 0;

            $requiereCompra = $faltante > 0 || (bool) $material->requiere_compra;
            $cantidadSugeridaCompra = $faltante > 0
                ? $faltante
                : ($requiereCompra ? (float) $material->cantidad_sugerida_compra : 0);

            $material->update([
                'stock_actual_calculado' => $stockActual,
                'stock_reservado_calculado' => $stockReservado,
                'stock_disponible_calculado' => $stockDisponible,
                'faltante_calculado' => $faltante,
                'requiere_compra' => $requiereCompra,
                'cantidad_sugerida_compra' => $cantidadSugeridaCompra,
                'compra_marcada_at' => $requiereCompra ? ($material->compra_marcada_at ?: now()) : null,
                'compra_marcada_por' => $requiereCompra ? ($material->compra_marcada_por ?: auth()->id()) : null,
            ]);
        }

        $orden->update([
            'estado' => $faltantes > 0 ? 'pendiente_material' : 'lista_para_apartar',
            'calculada_at' => now(),
            'calculada_por' => auth()->id(),
        ]);

        return [
            'materiales' => $orden->materiales->count(),
            'faltantes' => $faltantes,
        ];
    }
    public function apartarOrdenFabricacion(HuentitanOrdenFabricacion $orden)
    {
        $almacen = $this->almacenHuentitan();

        try {
            DB::transaction(function () use ($almacen, $orden) {
                $orden = HuentitanOrdenFabricacion::query()
                    ->whereKey($orden->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($orden->estado, ['lista_para_apartar'], true)) {
                    throw new \RuntimeException('La orden debe estar lista para apartar material. Revisa disponibilidad antes de apartar.');
                }

                $resultado = $this->revisarDisponibilidadOrdenFabricacion($orden, $almacen);
                $orden->refresh()->load('materiales');

                if (($resultado['faltantes'] ?? 0) > 0 || $orden->estado !== 'lista_para_apartar') {
                    throw new \RuntimeException('La orden aun tiene faltantes. No se puede apartar material.');
                }

                if ($orden->materiales->isEmpty()) {
                    throw new \RuntimeException('La orden no tiene materiales para apartar.');
                }

                foreach ($orden->materiales as $material) {
                    $cantidad = round((float) $material->cantidad_requerida, 3);

                    if ($cantidad <= 0) {
                        throw new \RuntimeException("El material {$material->material_nombre} tiene cantidad requerida invalida.");
                    }

                    $stockRow = DB::table('inventario_stock')
                        ->where('almacen_id', $almacen->id)
                        ->where('producto_id', $material->material_producto_id)
                        ->lockForUpdate()
                        ->first();

                    $stockActual = (float) ($stockRow->stock_actual ?? 0);
                    $stockReservado = (float) ($stockRow->stock_reservado ?? 0);
                    $stockDisponible = max(0, $stockActual - $stockReservado);

                    if (! $stockRow || $cantidad > $stockDisponible) {
                        throw new \RuntimeException("Stock insuficiente para '{$material->material_nombre}'. Disponible: {$stockDisponible}, requiere: {$cantidad}.");
                    }

                    DB::table('inventario_stock')
                        ->where('almacen_id', $almacen->id)
                        ->where('producto_id', $material->material_producto_id)
                        ->update([
                            'stock_reservado' => $stockReservado + $cantidad,
                            'updated_at' => now(),
                        ]);

                    $nuevoReservado = $stockReservado + $cantidad;

                    $material->update([
                        'stock_reservado_calculado' => $nuevoReservado,
                        'stock_disponible_calculado' => max(0, $stockActual - $nuevoReservado),
                        'faltante_calculado' => 0,
                        'cantidad_apartada' => $cantidad,
                        'apartada_at' => now(),
                        'apartada_por' => auth()->id(),
                    ]);
                }

                $orden->update([
                    'estado' => 'apartada',
                    'apartada_at' => now(),
                    'apartada_por' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('huentitan.ordenes-fabricacion.show', $orden)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('huentitan.ordenes-fabricacion.show', $orden)
            ->with('status', 'Material apartado para la orden ' . $orden->folio . '.');
    }
    public function enviarProduccionOrdenFabricacion(HuentitanOrdenFabricacion $orden)
    {
        $almacen = $this->almacenHuentitan();

        try {
            DB::transaction(function () use ($almacen, $orden) {
                $orden = HuentitanOrdenFabricacion::query()
                    ->with('materiales.material')
                    ->whereKey($orden->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($orden->estado !== 'apartada') {
                    throw new \RuntimeException('Solo se pueden enviar a produccion ordenes con material apartado.');
                }

                if ($orden->produccion_iniciada_at) {
                    throw new \RuntimeException('Esta orden ya fue enviada a produccion.');
                }

                if ($orden->materiales->isEmpty()) {
                    throw new \RuntimeException('La orden no tiene materiales para consumir.');
                }

                foreach ($orden->materiales as $material) {
                    $cantidad = round((float) $material->cantidad_apartada, 3);

                    if ($cantidad <= 0) {
                        throw new \RuntimeException("El material {$material->material_nombre} no tiene cantidad apartada para consumir.");
                    }

                    if (! $material->material_producto_id) {
                        throw new \RuntimeException("El material {$material->material_nombre} no tiene producto ligado al catalogo.");
                    }

                    $stockRow = DB::table('inventario_stock')
                        ->where('almacen_id', $almacen->id)
                        ->where('producto_id', $material->material_producto_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stockRow) {
                        throw new \RuntimeException("No existe stock para '{$material->material_nombre}' en HUENTITAN.");
                    }

                    $stockActual = (float) ($stockRow->stock_actual ?? 0);
                    $stockReservado = (float) ($stockRow->stock_reservado ?? 0);

                    if ($stockReservado + 0.0005 < $cantidad) {
                        throw new \RuntimeException("El reservado de '{$material->material_nombre}' es menor al material apartado. Reservado: {$stockReservado}, requiere: {$cantidad}.");
                    }

                    if ($stockActual + 0.0005 < $cantidad) {
                        throw new \RuntimeException("Stock insuficiente para consumir '{$material->material_nombre}'. Stock actual: {$stockActual}, requiere: {$cantidad}.");
                    }

                    $costoPromedio = (float) ($stockRow->costo_promedio ?? 0);
                    $valorTotal = (float) ($stockRow->valor_total ?? 0);
                    $nuevoStock = max(0, $stockActual - $cantidad);
                    $nuevoReservado = max(0, $stockReservado - $cantidad);
                    $nuevoValor = max(0, $valorTotal - ($cantidad * $costoPromedio));
                    $nuevoCostoPromedio = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;

                    DB::table('inventario_stock')
                        ->where('almacen_id', $almacen->id)
                        ->where('producto_id', $material->material_producto_id)
                        ->update([
                            'stock_actual' => $nuevoStock,
                            'stock_reservado' => $nuevoReservado,
                            'valor_total' => $nuevoValor,
                            'costo_promedio' => $nuevoCostoPromedio,
                            'updated_at' => now(),
                        ]);

                    DB::table('inventario_movimientos')->insert([
                        'almacen_id' => $almacen->id,
                        'producto_id' => $material->material_producto_id,
                        'documento_id' => $orden->id,
                        'fecha' => now(),
                        'tipo_movimiento' => 'out',
                        'cantidad' => $cantidad,
                        'costo_unitario' => $costoPromedio,
                        'saldo_cantidad' => $nuevoStock,
                        'obra_id' => null,
                        'residente_id' => null,
                        'creado_por' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $material->update([
                        'stock_actual_calculado' => $nuevoStock,
                        'stock_reservado_calculado' => $nuevoReservado,
                        'stock_disponible_calculado' => max(0, $nuevoStock - $nuevoReservado),
                        'faltante_calculado' => 0,
                        'cantidad_consumida' => $cantidad,
                        'consumida_at' => now(),
                        'consumida_por' => auth()->id(),
                    ]);
                }

                $orden->update([
                    'estado' => 'en_produccion',
                    'produccion_iniciada_at' => now(),
                    'produccion_iniciada_por' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('huentitan.ordenes-fabricacion.show', $orden)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('huentitan.ordenes-fabricacion.show', $orden)
            ->with('status', 'Orden enviada a produccion. Los insumos apartados fueron descontados del inventario.');
    }
    public function actualizarCompraMaterialOrden(Request $request, HuentitanOrdenFabricacion $orden, HuentitanOrdenFabricacionMaterial $material)
    {
        abort_unless((int) $material->orden_fabricacion_id === (int) $orden->id, 404);
        abort_if(in_array($orden->estado, ['en_produccion', 'cerrada', 'cancelada'], true), 422, 'La orden ya no permite cambiar materiales para compra.');

        $data = $request->validate([
            'requiere_compra' => ['nullable', 'boolean'],
            'cantidad_sugerida_compra' => ['nullable', 'numeric', 'min:0'],
        ]);

        $requiereCompra = $request->boolean('requiere_compra');
        $cantidadSugerida = (float) ($data['cantidad_sugerida_compra'] ?? 0);

        if ($requiereCompra && $cantidadSugerida <= 0) {
            $cantidadSugerida = (float) ($material->faltante_calculado > 0
                ? $material->faltante_calculado
                : $material->cantidad_requerida);
        }

        $material->update([
            'requiere_compra' => $requiereCompra,
            'cantidad_sugerida_compra' => $requiereCompra ? $cantidadSugerida : 0,
            'compra_marcada_at' => $requiereCompra ? now() : null,
            'compra_marcada_por' => $requiereCompra ? auth()->id() : null,
        ]);

        return redirect()
            ->route('huentitan.ordenes-fabricacion.show', $orden)
            ->with('status', 'Material actualizado para compra.');
    }
    public function guardarOrdenFabricacion(Request $request)
    {
        $almacen = $this->almacenHuentitan();

        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'cantidad_solicitada' => ['required', 'numeric', 'gt:0'],
            'fecha' => ['required', 'date'],
        ]);

        $producto = Producto::query()
            ->where('id', $data['producto_id'])
            ->where('sku', 'like', 'HUE-%')
            ->where('requiere_formula', true)
            ->with(['huentitanFormula.materiales.material'])
            ->firstOrFail();

        $formula = $producto->huentitanFormula;
        if (! $formula || $formula->materiales->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['producto_id' => 'El producto seleccionado necesita una formula con materiales antes de crear la orden.']);
        }

        if ((float) $formula->cantidad_base <= 0) {
            return back()
                ->withInput()
                ->withErrors(['producto_id' => 'La formula del producto debe tener una cantidad base mayor a cero.']);
        }

        $orden = DB::transaction(function () use ($almacen, $data, $producto, $formula) {
            $cantidadSolicitada = (float) $data['cantidad_solicitada'];
            $factor = $cantidadSolicitada / (float) $formula->cantidad_base;
            $materialIds = $formula->materiales->pluck('material_producto_id');
            $stockMap = InventarioStock::query()
                ->where('almacen_id', $almacen->id)
                ->whereIn('producto_id', $materialIds)
                ->get()
                ->keyBy('producto_id');

            $orden = HuentitanOrdenFabricacion::create([
                'folio' => $this->generarFolioOrdenFabricacion(),
                'producto_id' => $producto->id,
                'formula_id' => $formula->id,
                'cantidad_solicitada' => $cantidadSolicitada,
                'formula_cantidad_base' => $formula->cantidad_base,
                'formula_unidad_base' => $formula->unidad_base,
                'formula_merma_esperada_porcentaje' => $formula->merma_esperada_porcentaje,
                'formula_tiempo_estimado_minutos' => $formula->tiempo_estimado_minutos,
                'formula_notas' => $formula->notas,
                'costo_material_estimado' => 0,
                'fecha' => $data['fecha'],
                'estado' => 'borrador',
                'creado_por' => auth()->id(),
            ]);

            $costoTotal = 0;
            foreach ($formula->materiales as $materialFormula) {
                $material = $materialFormula->material;
                $stock = $stockMap->get($materialFormula->material_producto_id);
                $cantidadPorUnidad = (float) $materialFormula->cantidad;
                $merma = (float) $materialFormula->merma_porcentaje;
                $cantidadRequerida = $cantidadPorUnidad * $factor * (1 + ($merma / 100));
                $costoUnitario = (float) ($stock->costo_promedio ?? 0);
                $costoLinea = $cantidadRequerida * $costoUnitario;
                $costoTotal += $costoLinea;

                HuentitanOrdenFabricacionMaterial::create([
                    'orden_fabricacion_id' => $orden->id,
                    'formula_material_id' => $materialFormula->id,
                    'material_producto_id' => $material?->id,
                    'material_sku' => $material?->sku,
                    'material_nombre' => $material?->nombre ?? 'Material no disponible',
                    'cantidad_por_unidad' => $cantidadPorUnidad,
                    'cantidad_requerida' => $cantidadRequerida,
                    'unidad' => $materialFormula->unidad ?: $material?->unidad,
                    'merma_porcentaje' => $merma,
                    'costo_unitario_estimado' => $costoUnitario,
                    'costo_total_estimado' => $costoLinea,
                    'notas' => $materialFormula->notas,
                ]);
            }

            $orden->update(['costo_material_estimado' => $costoTotal]);

            $this->revisarDisponibilidadOrdenFabricacion($orden, $almacen);

            return $orden;
        });

        return redirect()
            ->route('huentitan.ordenes-fabricacion.index')
            ->with('status', 'Orden de fabricacion ' . $orden->folio . ' creada con disponibilidad revisada.');
    }

    public function index(Request $request)
    {
        $almacen = $this->almacenHuentitan();

        $cortes = InventarioCorte::query()
            ->where('almacen_id', $almacen->id)
            ->withCount('detalles')
            ->orderByDesc('fecha_hasta')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stocks = InventarioStock::query()
            ->with('producto')
            ->where('almacen_id', $almacen->id)
            ->whereHas('producto')
            ->orderByDesc('stock_actual')
            ->limit(25)
            ->get();

        $resumen = [
            'productos' => InventarioStock::query()->where('almacen_id', $almacen->id)->count(),
            'con_existencia' => InventarioStock::query()->where('almacen_id', $almacen->id)->where('stock_actual', '>', 0)->count(),
            'valor_total' => InventarioStock::query()->where('almacen_id', $almacen->id)->sum('valor_total'),
            'bajo_minimo' => DB::table('inventario_stock as stock')
                ->join('productos as productos', 'productos.id', '=', 'stock.producto_id')
                ->where('stock.almacen_id', $almacen->id)
                ->where('productos.stock_minimo', '>', 0)
                ->whereColumn('stock.stock_actual', '<=', 'productos.stock_minimo')
                ->count(),
        ];

        return view('inventario.huentitan.index', compact('almacen', 'cortes', 'stocks', 'resumen'));
    }

    public function importar(Request $request, HuentitanInventoryImportService $service)
    {
        $data = $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'archivo' => ['required', 'file', 'mimes:xlsx'],
            'titulo' => ['nullable', 'string', 'max:180'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $almacen = $this->almacenHuentitan();
        $path = $request->file('archivo')->getRealPath();

        $corte = $service->importarUltimaHoja($path, $almacen, [
            'fecha_desde' => $data['fecha_desde'],
            'fecha_hasta' => $data['fecha_hasta'],
            'titulo' => $data['titulo'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);

        return redirect()
            ->route('inventario.huentitan.cortes.show', $corte)
            ->with('status', 'Corte importado. Revisalo antes de aplicarlo al stock.');
    }

    public function show(InventarioCorte $corte)
    {
        $corte->load(['almacen', 'documento']);

        $detalles = $corte->detalles()
            ->with('producto')
            ->orderBy('numero_importacion')
            ->paginate(50)
            ->withQueryString();

        $resumenTipos = $corte->detalles()
            ->select('tipo_inventario', DB::raw('COUNT(*) as total'), DB::raw('SUM(existencia_actual) as existencia'), DB::raw('SUM(valor_actual) as valor'))
            ->groupBy('tipo_inventario')
            ->orderBy('tipo_inventario')
            ->get();

        return view('inventario.huentitan.show', compact('corte', 'detalles', 'resumenTipos'));
    }

    public function actualizarDetalle(Request $request, InventarioCorteDetalle $detalle)
    {
        $data = $request->validate([
            'tipo_inventario' => ['required', 'in:materia_prima,producto_terminado,subensamble'],
            'origen_abastecimiento' => ['required', 'in:compra,fabricacion,ambos'],
            'requiere_formula' => ['nullable', 'boolean'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'punto_reorden' => ['nullable', 'numeric', 'min:0'],
        ]);

        $detalle->update([
            'tipo_inventario' => $data['tipo_inventario'],
            'origen_abastecimiento' => $data['origen_abastecimiento'],
            'requiere_formula' => $request->boolean('requiere_formula'),
        ]);

        if ($detalle->producto) {
            $detalle->producto->update([
                'tipo_inventario' => $data['tipo_inventario'],
                'origen_abastecimiento' => $data['origen_abastecimiento'],
                'requiere_formula' => $request->boolean('requiere_formula'),
                'stock_minimo' => $data['stock_minimo'] ?? 0,
                'punto_reorden' => $data['punto_reorden'] ?? 0,
            ]);
        }

        return back()->with('status', 'Producto actualizado.');
    }

    public function aplicar(InventarioCorte $corte, HuentitanInventoryImportService $service)
    {
        $service->aplicarCorte($corte);

        return redirect()
            ->route('inventario.huentitan.cortes.show', $corte)
            ->with('status', 'Corte aplicado al inventario del almacen Huentitan.');
    }

    private function generarFolioOrdenFabricacion(): string
    {
        $prefijo = 'OF-HUE-' . now()->format('Ym') . '-';
        $ultimoFolio = HuentitanOrdenFabricacion::query()
            ->where('folio', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('folio')
            ->value('folio');

        $consecutivo = $ultimoFolio ? ((int) substr($ultimoFolio, -4)) + 1 : 1;

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    private function areaHuentitan(Almacen $almacen): ?Area
    {
        if ($almacen->area_id) {
            return $almacen->area()->first();
        }

        return Area::query()
            ->where('codigo', 'HT')
            ->orWhere('nombre', 'like', '%HUENTITAN%')
            ->orWhere('nombre', 'like', '%HUNTITAN%')
            ->first();
    }

    private function almacenHuentitan(): Almacen
    {
        $almacen = Almacen::query()
            ->where('codigo', self::ALMACEN_CODIGO)
            ->first();

        if ($almacen) {
            return $almacen;
        }

        $almacen = Almacen::query()
            ->where(function ($query) {
                $query->where('nombre', 'like', '%HUENTITAN%')
                    ->orWhere('nombre', 'like', '%HUNTITAN%');
            })
            ->first();

        if ($almacen) {
            $almacen->update(['codigo' => self::ALMACEN_CODIGO]);

            return $almacen;
        }

        return Almacen::create([
            'codigo' => self::ALMACEN_CODIGO,
            'nombre' => 'AL-HUENTITAN',
            'tipo' => 'general',
            'activo' => true,
        ]);
    }
}


































