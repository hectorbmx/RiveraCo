<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\Herramienta;
use App\Models\HuentitanFormula;
use App\Models\HuentitanFormulaHerramienta;
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
use App\Support\Inventario\UnidadMedidaCatalogo;

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


    public function herramientas(Request $request)
    {
        $almacen = $this->almacenHuentitan();
        $busqueda = trim((string) $request->query('q', ''));
        $estado = $request->query('estado');
        $activo = $request->query('activo', 'activos');

        $estadosPermitidos = ['activa', 'en_mantenimiento', 'baja'];
        $estado = in_array($estado, $estadosPermitidos, true) ? $estado : null;
        $activo = in_array($activo, ['activos', 'inactivos', 'todos'], true) ? $activo : 'activos';

        $herramientas = Herramienta::query()
            ->with('proveedor:id,nombre')
            ->where('almacen_id', $almacen->id)
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($herramienta) use ($busqueda) {
                    $herramienta->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('codigo', 'like', '%' . $busqueda . '%')
                        ->orWhere('descripcion', 'like', '%' . $busqueda . '%')
                        ->orWhere('marca', 'like', '%' . $busqueda . '%')
                        ->orWhere('modelo', 'like', '%' . $busqueda . '%')
                        ->orWhere('numero_serie', 'like', '%' . $busqueda . '%');
                });
            })
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($activo === 'activos', fn ($query) => $query->where('activo', true))
            ->when($activo === 'inactivos', fn ($query) => $query->where('activo', false))
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        $resumenHerramientas = [
            'total' => Herramienta::query()->where('almacen_id', $almacen->id)->count(),
            'activas' => Herramienta::query()->where('almacen_id', $almacen->id)->where('activo', true)->count(),
            'en_mantenimiento' => Herramienta::query()->where('almacen_id', $almacen->id)->where('estado', 'en_mantenimiento')->count(),
            'valor' => Herramienta::query()->where('almacen_id', $almacen->id)->where('activo', true)->sum('costo'),
        ];

        return view('huentitan.herramientas.index', compact('almacen', 'herramientas', 'resumenHerramientas', 'busqueda', 'estado', 'activo'));
    }

    public function crearHerramienta()
    {
        $almacen = $this->almacenHuentitan();
        $herramienta = new Herramienta([
            'almacen_id' => $almacen->id,
            'costo' => 0,
            'costo_residual' => 0,
            'estado' => 'activa',
            'activo' => true,
            'fecha_registro' => now()->toDateString(),
        ]);
        $modo = 'crear';

        return view('huentitan.herramientas.create', compact('almacen', 'herramienta', 'modo'));
    }

    public function guardarHerramienta(Request $request)
    {
        $almacen = $this->almacenHuentitan();
        $data = $this->validarHerramientaHuentitan($request);

        $herramienta = DB::transaction(function () use ($almacen, $data, $request) {
            return Herramienta::create([
                'almacen_id' => $almacen->id,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'codigo' => $this->generarCodigoHerramientaHuentitan($almacen),
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'costo' => $data['costo'] ?? 0,
                'vida_util_piezas' => $data['vida_util_piezas'] ?? null,
                'costo_residual' => $data['costo_residual'] ?? 0,
                'fecha_compra' => $data['fecha_compra'] ?? null,
                'fecha_registro' => $data['fecha_registro'] ?? now()->toDateString(),
                'proveedor_nombre' => $data['proveedor_nombre'] ?? null,
                'marca' => $data['marca'] ?? null,
                'modelo' => $data['modelo'] ?? null,
                'numero_serie' => $data['numero_serie'] ?? null,
                'estado' => $data['estado'],
                'activo' => $request->boolean('activo', true),
            ]);
        });

        return redirect()
            ->route('huentitan.herramientas.edit', $herramienta)
            ->with('status', 'Herramienta HUENTITAN creada correctamente.');
    }

    public function editarHerramienta(Herramienta $herramienta)
    {
        $almacen = $this->almacenHuentitan();
        $this->validarHerramientaPerteneceHuentitan($herramienta, $almacen);
        $modo = 'editar';

        return view('huentitan.herramientas.edit', compact('almacen', 'herramienta', 'modo'));
    }

    public function actualizarHerramienta(Request $request, Herramienta $herramienta)
    {
        $almacen = $this->almacenHuentitan();
        $this->validarHerramientaPerteneceHuentitan($herramienta, $almacen);
        $data = $this->validarHerramientaHuentitan($request);

        $herramienta->update([
            'proveedor_id' => $data['proveedor_id'] ?? null,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'costo' => $data['costo'] ?? 0,
            'vida_util_piezas' => $data['vida_util_piezas'] ?? null,
            'costo_residual' => $data['costo_residual'] ?? 0,
            'fecha_compra' => $data['fecha_compra'] ?? null,
            'fecha_registro' => $data['fecha_registro'] ?? null,
            'proveedor_nombre' => $data['proveedor_nombre'] ?? null,
            'marca' => $data['marca'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'numero_serie' => $data['numero_serie'] ?? null,
            'estado' => $data['estado'],
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()
            ->route('huentitan.herramientas.edit', $herramienta)
            ->with('status', 'Herramienta HUENTITAN actualizada correctamente.');
    }

    public function desactivarHerramienta(Herramienta $herramienta)
    {
        $almacen = $this->almacenHuentitan();
        $this->validarHerramientaPerteneceHuentitan($herramienta, $almacen);

        $herramienta->update([
            'activo' => false,
            'estado' => 'baja',
        ]);

        return redirect()
            ->route('huentitan.herramientas.index')
            ->with('status', 'Herramienta HUENTITAN desactivada correctamente.');
    }

    private function validarHerramientaHuentitan(Request $request): array
    {
        return $request->validate([
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'vida_util_piezas' => ['nullable', 'integer', 'min:1'],
            'costo_residual' => ['nullable', 'numeric', 'min:0'],
            'fecha_compra' => ['nullable', 'date'],
            'fecha_registro' => ['nullable', 'date'],
            'proveedor_nombre' => ['nullable', 'string', 'max:150'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', 'in:activa,en_mantenimiento,baja'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function validarHerramientaPerteneceHuentitan(Herramienta $herramienta, Almacen $almacen): void
    {
        abort_unless((int) $herramienta->almacen_id === (int) $almacen->id, 404);
    }

    private function generarCodigoHerramientaHuentitan(Almacen $almacen): string
    {
        $ultimoCodigo = Herramienta::query()
            ->where('almacen_id', $almacen->id)
            ->where('codigo', 'like', 'HUE-HER-%')
            ->whereRaw("codigo REGEXP '^HUE-HER-[0-9]+$'")
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(codigo, 9) AS UNSIGNED) DESC')
            ->value('codigo');

        $ultimoNumero = 0;
        $padding = 6;
        if ($ultimoCodigo && preg_match('/HUE-HER-(\d+)$/', (string) $ultimoCodigo, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $padding = max($padding, strlen($matches[1]));
        }

        do {
            $codigo = 'HUE-HER-' . str_pad((string) (++$ultimoNumero), $padding, '0', STR_PAD_LEFT);
        } while (Herramienta::query()->where('almacen_id', $almacen->id)->where('codigo', $codigo)->exists());

        return $codigo;
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
    public function crearProducto()
    {
        $almacen = $this->almacenHuentitan();
        $producto = new Producto([
            'tipo_inventario' => 'materia_prima',
            'origen_abastecimiento' => 'compra',
            'requiere_formula' => false,
            'stock_minimo' => 0,
            'activo' => true,
            'unidad_compra' => 'PZA',
            'cantidad_por_unidad_compra' => 1,
            'unidad_base' => 'PZA',
        ]);
        $modo = 'crear';
        $unidadesCompra = UnidadMedidaCatalogo::compra();
        $unidadesBase = UnidadMedidaCatalogo::base();

        return view('huentitan.productos.create', compact('almacen', 'producto', 'modo', 'unidadesCompra', 'unidadesBase'));
    }

    public function guardarProducto(Request $request)
    {
        $data = $this->validarProductoHuentitan($request);
        $almacen = $this->almacenHuentitan();

        $producto = DB::transaction(function () use ($data, $request, $almacen) {
            $producto = Producto::create([
                'sku' => $this->generarSkuHuentitan(),
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'unidad' => UnidadMedidaCatalogo::textoLegacy($data['unidad_compra'], $data['cantidad_por_unidad_compra'], $data['unidad_base']),
                'unidad_compra' => $data['unidad_compra'],
                'cantidad_por_unidad_compra' => $data['cantidad_por_unidad_compra'],
                'unidad_base' => $data['unidad_base'],
                'tipo_inventario' => $data['tipo_inventario'],
                'origen_abastecimiento' => $data['origen_abastecimiento'],
                'requiere_formula' => $request->boolean('requiere_formula'),
                'stock_minimo' => $data['stock_minimo'] ?? 0,
                'activo' => $request->boolean('activo', true),
            ]);

            InventarioStock::firstOrCreate(
                [
                    'almacen_id' => $almacen->id,
                    'producto_id' => $producto->id,
                ],
                [
                    'stock_actual' => 0,
                    'stock_reservado' => 0,
                    'valor_total' => 0,
                    'costo_promedio' => 0,
                ]
            );

            return $producto;
        });

        return redirect()
            ->route('huentitan.productos.show', [
                'producto' => $producto->id,
                'tab' => $producto->requiere_formula ? 'formula' : 'resumen',
            ])
            ->with('status', 'Producto HUENTITAN creado correctamente.');
    }

    public function editarProducto(Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $almacen = $this->almacenHuentitan();
        $modo = 'editar';
        $unidadesCompra = UnidadMedidaCatalogo::compra();
        $unidadesBase = UnidadMedidaCatalogo::base();

        return view('huentitan.productos.edit', compact('almacen', 'producto', 'modo', 'unidadesCompra', 'unidadesBase'));
    }

    public function actualizarProductoCatalogo(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $this->validarProductoHuentitan($request);

        $producto->update([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'unidad' => UnidadMedidaCatalogo::textoLegacy($data['unidad_compra'], $data['cantidad_por_unidad_compra'], $data['unidad_base']),
                'unidad_compra' => $data['unidad_compra'],
                'cantidad_por_unidad_compra' => $data['cantidad_por_unidad_compra'],
                'unidad_base' => $data['unidad_base'],
            'tipo_inventario' => $data['tipo_inventario'],
            'origen_abastecimiento' => $data['origen_abastecimiento'],
            'unidad' => UnidadMedidaCatalogo::textoLegacy($data['unidad_compra'], $data['cantidad_por_unidad_compra'], $data['unidad_base']),
            'unidad_compra' => $data['unidad_compra'],
            'cantidad_por_unidad_compra' => $data['cantidad_por_unidad_compra'],
            'unidad_base' => $data['unidad_base'],
            'requiere_formula' => $request->boolean('requiere_formula'),
            'stock_minimo' => $data['stock_minimo'] ?? 0,
            'activo' => $request->boolean('activo'),
        ]);

        InventarioStock::firstOrCreate(
            [
                'almacen_id' => $this->almacenHuentitan()->id,
                'producto_id' => $producto->id,
            ],
            [
                'stock_actual' => 0,
                'stock_reservado' => 0,
                'valor_total' => 0,
                'costo_promedio' => 0,
            ]
        );

        return redirect()
            ->route('huentitan.productos.show', [
                'producto' => $producto->id,
                'tab' => $producto->requiere_formula ? 'formula' : 'resumen',
            ])
            ->with('status', 'Producto HUENTITAN actualizado correctamente.');
    }

    public function desactivarProducto(Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $producto->update(['activo' => false]);

        return redirect()
            ->route('huentitan.productos.index')
            ->with('status', 'Producto HUENTITAN desactivado correctamente.');
    }

    private function validarProductoHuentitan(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'unidad_compra' => ['required', UnidadMedidaCatalogo::regla()],
            'cantidad_por_unidad_compra' => ['required', 'numeric', 'gt:0'],
            'unidad_base' => ['required', UnidadMedidaCatalogo::regla()],
            'tipo_inventario' => ['required', 'in:materia_prima,producto_terminado,subensamble'],
            'origen_abastecimiento' => ['required', 'in:compra,fabricacion,ambos'],
            'requiere_formula' => ['nullable', 'boolean'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function generarSkuHuentitan(): string
    {
        $ultimoSku = Producto::query()
            ->where('sku', 'like', 'HUE-%')
            ->whereRaw("sku REGEXP '^HUE-[0-9]+$'")
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(sku, 5) AS UNSIGNED) DESC')
            ->value('sku');

        $ultimoNumero = 0;
        $padding = 6;
        if ($ultimoSku && preg_match('/HUE-(\d+)$/', (string) $ultimoSku, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $padding = max($padding, strlen($matches[1]));
        }

        do {
            $sku = 'HUE-' . str_pad((string) (++$ultimoNumero), $padding, '0', STR_PAD_LEFT);
        } while (Producto::query()->where('sku', $sku)->exists());

        return $sku;
    }
    public function actualizarProductoGeneral(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'tipo_inventario' => ['required', 'in:materia_prima,producto_terminado,subensamble'],
            'origen_abastecimiento' => ['required', 'in:compra,fabricacion,ambos'],
            'unidad_compra' => ['required', UnidadMedidaCatalogo::regla()],
            'cantidad_por_unidad_compra' => ['required', 'numeric', 'gt:0'],
            'unidad_base' => ['required', UnidadMedidaCatalogo::regla()],
            'requiere_formula' => ['nullable', 'boolean'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $producto->update([
            'tipo_inventario' => $data['tipo_inventario'],
            'origen_abastecimiento' => $data['origen_abastecimiento'],
            'unidad' => UnidadMedidaCatalogo::textoLegacy($data['unidad_compra'], $data['cantidad_por_unidad_compra'], $data['unidad_base']),
            'unidad_compra' => $data['unidad_compra'],
            'cantidad_por_unidad_compra' => $data['cantidad_por_unidad_compra'],
            'unidad_base' => $data['unidad_base'],
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
            'diametro' => ['nullable', 'numeric', 'min:0'],
            'largo' => ['nullable', 'numeric', 'min:0'],
            'ancho' => ['nullable', 'numeric', 'min:0'],
            'alto' => ['nullable', 'numeric', 'min:0'],
            'espesor_calibre' => ['nullable', 'numeric', 'min:0'],
            'peso' => ['nullable', 'numeric', 'min:0'],
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
            ->get(['id', 'sku', 'nombre', 'unidad', 'unidad_compra', 'cantidad_por_unidad_compra', 'unidad_base']);

        $almacen = $this->almacenHuentitan();
        $stockMap = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->whereIn('producto_id', $materiales->pluck('id'))
            ->get()
            ->keyBy('producto_id');

        return response()->json($materiales->map(function ($material) use ($stockMap) {
            $stock = $stockMap->get($material->id);

            $unidadBase = $material->unidad_base ?: $material->unidad;
            $unidadCompra = $material->unidad_compra ?: $unidadBase;
            $cantidadPorUnidad = (float) ($material->cantidad_por_unidad_compra ?: 1);
            $cantidadPorUnidadLabel = rtrim(rtrim(number_format($cantidadPorUnidad, 6, '.', ''), '0'), '.');
            $equivalencia = $unidadCompra && $unidadBase
                ? '1 ' . $unidadCompra . ' = ' . $cantidadPorUnidadLabel . ' ' . $unidadBase
                : null;

            return [
                'id' => $material->id,
                'sku' => $material->sku,
                'nombre' => $material->nombre,
                'unidad' => $unidadBase,
                'unidad_consumo' => $unidadBase,
                'unidad_base' => $unidadBase,
                'unidad_compra' => $unidadCompra,
                'cantidad_por_unidad_compra' => $cantidadPorUnidad,
                'equivalencia' => $equivalencia,
                'costo_promedio' => (float) ($stock->costo_promedio ?? 0),
                'label' => trim(($material->sku ? $material->sku . ' - ' : '') . $material->nombre),
            ];
        }));
    }


    public function buscarHerramientasFormula(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $almacen = $this->almacenHuentitan();

        $herramientas = Herramienta::query()
            ->where('almacen_id', $almacen->id)
            ->where('activo', true)
            ->where(function ($query) use ($term) {
                $query->where('nombre', 'like', '%' . $term . '%')
                    ->orWhere('codigo', 'like', '%' . $term . '%')
                    ->orWhere('marca', 'like', '%' . $term . '%')
                    ->orWhere('modelo', 'like', '%' . $term . '%')
                    ->orWhere('numero_serie', 'like', '%' . $term . '%');
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'codigo', 'nombre', 'costo', 'costo_residual', 'vida_util_piezas', 'marca', 'modelo']);

        return response()->json($herramientas->map(function ($herramienta) {
            $vidaUtil = (int) ($herramienta->vida_util_piezas ?? 0);
            $costoSugerido = $vidaUtil > 0
                ? max(((float) $herramienta->costo - (float) $herramienta->costo_residual) / $vidaUtil, 0)
                : null;

            return [
                'id' => $herramienta->id,
                'codigo' => $herramienta->codigo,
                'nombre' => $herramienta->nombre,
                'marca' => $herramienta->marca,
                'modelo' => $herramienta->modelo,
                'costo' => (float) $herramienta->costo,
                'vida_util_piezas' => $herramienta->vida_util_piezas,
                'costo_sugerido' => $costoSugerido,
                'label' => trim(($herramienta->codigo ? $herramienta->codigo . ' - ' : '') . $herramienta->nombre),
            ];
        }));
    }

    public function agregarProductoFormulaHerramienta(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'herramienta_id' => ['required', 'exists:herramientas,id'],
            'cantidad' => ['nullable', 'numeric', 'gt:0'],
            'costo_unitario_aplicado' => ['required', 'numeric', 'min:0'],
            'metodo_calculo' => ['nullable', 'in:manual,prorrateo_por_piezas'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        $almacen = $this->almacenHuentitan();
        $herramienta = Herramienta::query()
            ->where('id', $data['herramienta_id'])
            ->where('almacen_id', $almacen->id)
            ->where('activo', true)
            ->firstOrFail();

        $producto->update(['requiere_formula' => true]);

        $formula = HuentitanFormula::firstOrCreate(
            ['producto_id' => $producto->id],
            [
                'cantidad_base' => 1,
                'unidad_base' => $producto->unidad_base ?: $producto->unidad,
                'merma_esperada_porcentaje' => 0,
            ]
        );

        HuentitanFormulaHerramienta::updateOrCreate(
            [
                'formula_id' => $formula->id,
                'herramienta_id' => $herramienta->id,
            ],
            [
                'cantidad' => $data['cantidad'] ?? 1,
                'costo_unitario_aplicado' => $data['costo_unitario_aplicado'],
                'metodo_calculo' => $data['metodo_calculo'] ?? 'manual',
                'notas' => $data['notas'] ?? null,
            ]
        );

        return redirect()
            ->route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'formula'])
            ->with('status', 'Herramienta agregada al precio unitario.');
    }

    public function eliminarProductoFormulaHerramienta(Producto $producto, HuentitanFormulaHerramienta $herramienta)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);
        abort_unless($herramienta->formula && (int) $herramienta->formula->producto_id === (int) $producto->id, 404);

        $herramienta->delete();

        return redirect()
            ->route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'formula'])
            ->with('status', 'Herramienta eliminada del precio unitario.');
    }

    public function actualizarProductoFormula(Request $request, Producto $producto)
    {
        abort_unless(str_starts_with((string) $producto->sku, 'HUE-'), 404);

        $data = $request->validate([
            'cantidad_base' => ['required', 'numeric', 'gt:0'],
            'unidad_base' => ['nullable', UnidadMedidaCatalogo::regla()],
            'merma_esperada_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tiempo_estimado_minutos' => ['nullable', 'integer', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $producto->update(['requiere_formula' => true]);

        HuentitanFormula::updateOrCreate(
            ['producto_id' => $producto->id],
            [
                'cantidad_base' => $data['cantidad_base'],
                'unidad_base' => $data['unidad_base'] ?: ($producto->unidad_base ?: $producto->unidad),
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
            'unidad' => ['nullable', UnidadMedidaCatalogo::regla()],
            'merma_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metodo_costo' => ['nullable', 'in:promedio_inventario,manual'],
            'costo_unitario_override' => ['nullable', 'numeric', 'min:0'],
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
                'unidad_base' => $producto->unidad_base ?: $producto->unidad,
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
                'unidad' => $data['unidad'] ?: ($material->unidad_base ?: $material->unidad),
                'merma_porcentaje' => $data['merma_porcentaje'] ?? 0,
                'metodo_costo' => $data['metodo_costo'] ?? 'promedio_inventario',
                'costo_unitario_override' => ($data['metodo_costo'] ?? 'promedio_inventario') === 'manual' ? ($data['costo_unitario_override'] ?? 0) : null,
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
            ->with(['materiales.material', 'herramientas.herramienta'])
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
            ->get(['id', 'sku', 'nombre', 'unidad', 'unidad_base']);

        $materialIdsFormula = $formulaProducto
            ? $formulaProducto->materiales->pluck('material_producto_id')
            : collect();

        $materialStockIds = $materialesDisponibles
            ->pluck('id')
            ->merge($materialIdsFormula)
            ->filter()
            ->unique()
            ->values();

        $materialStockMap = InventarioStock::query()
            ->where('almacen_id', $almacen->id)
            ->whereIn('producto_id', $materialStockIds)
            ->get()
            ->keyBy('producto_id');

        $costoMaterialesFormula = $formulaProducto
            ? $formulaProducto->materiales->sum(function ($materialFormula) use ($materialStockMap) {
                $stock = $materialStockMap->get($materialFormula->material_producto_id);
                $cantidad = (float) $materialFormula->cantidad;
                $merma = (float) $materialFormula->merma_porcentaje;

                $costoUnitario = $materialFormula->metodo_costo === 'manual'
                    ? (float) ($materialFormula->costo_unitario_override ?? 0)
                    : (float) ($stock->costo_promedio ?? 0);

                return $cantidad * (1 + ($merma / 100)) * $costoUnitario;
            })
            : 0;

        $costoHerramientasFormula = $formulaProducto
            ? $formulaProducto->herramientas->sum(fn ($herramientaFormula) => (float) $herramientaFormula->cantidad * (float) $herramientaFormula->costo_unitario_aplicado)
            : 0;

        $costoFormulaEstimado = $costoMaterialesFormula + $costoHerramientasFormula;

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

        $unidadesCompra = UnidadMedidaCatalogo::compra();
        $unidadesBase = UnidadMedidaCatalogo::base();

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
            'costoMaterialesFormula',
            'costoHerramientasFormula',
            'costoFormulaEstimado',
            'costoFormulaUnitario',
            'resumen',
            'unidadesCompra',
            'unidadesBase'
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
            ->get(['id', 'sku', 'nombre', 'unidad', 'unidad_base']);

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
            'unidad_compra' => 'PZA',
            'cantidad_por_unidad_compra' => 1,
            'unidad_base' => 'PZA',
        ]);
    }
}








