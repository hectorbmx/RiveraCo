<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\HuentitanSalida;
use App\Models\HuentitanSalidaDetalle;
use App\Models\InventarioStock;
use App\Models\Obra;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HuentitanSalidaController extends Controller
{
    private const ALMACEN_CODIGO = 'AL-HUENTITAN';

    public function index(Request $request)
    {
        $almacen = $this->almacenHuentitan();
        $busqueda = trim((string) $request->query('q', ''));
        $estado = $request->query('estado', 'todos');
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');

        $query = HuentitanSalida::query()
            ->with(['almacen', 'obra', 'usuario', 'detalles.producto'])
            ->where('almacen_id', $almacen->id)
            ->latest('fecha');

        if ($estado && in_array($estado, ['borrador', 'aplicada', 'cancelada'], true)) {
            $query->where('estado', $estado);
        }

        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('folio', 'like', "%{$busqueda}%")
                    ->orWhere('tipo_destino', 'like', "%{$busqueda}%")
                    ->orWhereHas('obra', function ($obraQuery) use ($busqueda) {
                        $obraQuery->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('clave_obra', 'like', "%{$busqueda}%");
                    });
            });
        }

        $resumen = [
            'total' => HuentitanSalida::query()->where('almacen_id', $almacen->id)->count(),
            'aplicadas' => HuentitanSalida::query()->where('almacen_id', $almacen->id)->where('estado', 'aplicada')->count(),
            'borrador' => HuentitanSalida::query()->where('almacen_id', $almacen->id)->where('estado', 'borrador')->count(),
            'canceladas' => HuentitanSalida::query()->where('almacen_id', $almacen->id)->where('estado', 'cancelada')->count(),
        ];

        $salidas = $query->paginate(20)->withQueryString();

        return response()->view('huentitan.salidas.index', compact('almacen', 'salidas', 'busqueda', 'estado', 'fechaDesde', 'fechaHasta', 'resumen'));
    }

    public function create()
    {
        $almacen = $this->almacenHuentitan();

        $obras = Obra::query()
            ->whereNotIn('estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA])
            ->orderBy('clave_obra')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'clave_obra', 'estatus_nuevo']);

        $productos = Producto::query()
            ->leftJoin('inventario_stock as stock', function ($join) use ($almacen) {
                $join->on('stock.producto_id', '=', 'productos.id')
                    ->where('stock.almacen_id', $almacen->id);
            })
            ->where('productos.sku', 'like', 'HUE-%')
            ->orderBy('productos.nombre')
            ->get([
                'productos.id',
                'productos.sku',
                'productos.nombre',
                'productos.unidad',
                'productos.tipo_inventario',
                DB::raw('COALESCE(stock.stock_actual, 0) as stock_actual'),
                DB::raw('COALESCE(stock.stock_reservado, 0) as stock_reservado'),
                DB::raw('COALESCE(stock.costo_promedio, 0) as costo_promedio'),
            ])
            ->map(function ($producto) {
                $stockActual = (float) $producto->stock_actual;
                $stockReservado = (float) $producto->stock_reservado;

                return [
                    'id' => (int) $producto->id,
                    'sku' => (string) $producto->sku,
                    'nombre' => (string) $producto->nombre,
                    'unidad' => $producto->unidad ?: 'PZA',
                    'tipo_inventario' => $producto->tipo_inventario ?: 'sin_clasificar',
                    'stock_actual' => $stockActual,
                    'stock_reservado' => $stockReservado,
                    'stock_disponible' => max(0, $stockActual - $stockReservado),
                    'costo_promedio' => (float) $producto->costo_promedio,
                ];
            })
            ->values();

        return response()->view('huentitan.salidas.create', compact('almacen', 'obras', 'productos'));
    }

    public function store(Request $request)
    {
        $almacen = $this->almacenHuentitan();

        $data = $request->validate([
            'obra_id' => ['required', 'integer', 'exists:obras,id'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'detalles.*.unidad' => ['nullable', 'string', 'max:30'],
        ], [
            'obra_id.required' => 'Selecciona la obra destino.',
            'detalles.required' => 'Agrega al menos un producto a la salida.',
            'detalles.min' => 'Agrega al menos un producto a la salida.',
            'detalles.*.cantidad.gt' => 'Todas las cantidades deben ser mayores a cero.',
        ]);

        $obraValida = Obra::query()
            ->whereKey($data['obra_id'])
            ->whereNotIn('estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA])
            ->exists();

        if (! $obraValida) {
            return back()
                ->withErrors(['obra_id' => 'La obra seleccionada no esta disponible para recibir salidas.'])
                ->withInput();
        }

        $productoIds = collect($data['detalles'])->pluck('producto_id')->map(fn ($id) => (int) $id)->values();

        if ($productoIds->duplicates()->isNotEmpty()) {
            return back()
                ->withErrors(['detalles' => 'No repitas productos en la misma salida. Ajusta la cantidad en una sola partida.'])
                ->withInput();
        }

        $productos = Producto::query()
            ->whereIn('id', $productoIds)
            ->where('sku', 'like', 'HUE-%')
            ->get()
            ->keyBy('id');

        if ($productos->count() !== $productoIds->count()) {
            return back()
                ->withErrors(['detalles' => 'Todos los productos deben pertenecer al catalogo HUENTITAN.'])
                ->withInput();
        }

        try {
            $salida = DB::transaction(function () use ($data, $almacen, $productos) {
                $salida = HuentitanSalida::create([
                    'folio' => $this->generarFolioSalida(),
                    'almacen_id' => $almacen->id,
                    'tipo_destino' => 'obra',
                    'obra_id' => $data['obra_id'],
                    'fecha' => $data['fecha'],
                    'estado' => 'borrador',
                    'usuario_id' => auth()->id(),
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                foreach ($data['detalles'] as $detalle) {
                    $producto = $productos->get((int) $detalle['producto_id']);
                    $cantidad = round((float) $detalle['cantidad'], 3);
                    $stock = InventarioStock::query()
                        ->where('almacen_id', $almacen->id)
                        ->where('producto_id', $producto->id)
                        ->first();

                    $stockActual = (float) ($stock->stock_actual ?? 0);
                    $stockReservado = (float) ($stock->stock_reservado ?? 0);
                    $stockDisponible = max(0, $stockActual - $stockReservado);

                    $cantidadFaltante = max(0, $cantidad - $stockDisponible);
                    $costoUnitario = (float) ($stock->costo_promedio ?? 0);

                    HuentitanSalidaDetalle::create([
                        'huentitan_salida_id' => $salida->id,
                        'producto_id' => $producto->id,
                        'descripcion' => $producto->nombre,
                        'unidad' => $detalle['unidad'] ?: ($producto->unidad ?: 'PZA'),
                        'cantidad_solicitada' => $cantidad,
                        'cantidad_salida' => $cantidad,
                        'stock_disponible_snapshot' => $stockDisponible,
                        'cantidad_faltante' => $cantidadFaltante,
                        'requiere_compra' => $cantidadFaltante > 0,
                        'cantidad_sugerida_compra' => $cantidadFaltante,
                        'costo_unitario' => $costoUnitario,
                        'importe' => round($cantidad * $costoUnitario, 2),
                    ]);
                }

                return $salida;
            });
        } catch (\RuntimeException $exception) {
            return back()
                ->withErrors(['detalles' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('huentitan.salidas.show', $salida)
            ->with('status', "Salida {$salida->folio} guardada exitosamente en estado borrador.");
    }

    public function show(HuentitanSalida $salida)
    {
        $almacen = $this->almacenHuentitan();
        abort_unless((int) $salida->almacen_id === (int) $almacen->id, 404);

        $salida->load(['obra', 'usuario', 'aplicadaPor', 'canceladaPor', 'detalles.producto']);
        $estadoPartidas = $this->estadoPartidasActuales($salida, $almacen);
        $faltantesActuales = $estadoPartidas->filter(fn ($item) => $item['faltante'] > 0)->values();
        $estadoOperativo = $salida->isBorrador()
            ? ($faltantesActuales->isEmpty() ? 'listo_para_aplicar' : 'con_faltantes')
            : $salida->estado;

        return response()->view('huentitan.salidas.show', compact('almacen', 'salida', 'faltantesActuales', 'estadoPartidas', 'estadoOperativo'));
    }

    public function aplicar(HuentitanSalida $salida)
    {
        $almacen = $this->almacenHuentitan();
        abort_unless((int) $salida->almacen_id === (int) $almacen->id, 404);

        $salida->load('detalles.producto');
        $faltantes = $this->estadoPartidasActuales($salida, $almacen)->filter(fn ($item) => $item['faltante'] > 0);

        if ($faltantes->isNotEmpty()) {
            return redirect()
                ->route('huentitan.salidas.show', $salida)
                ->with('error', 'No se puede aplicar la salida porque aun hay material faltante. Genera la orden de compra, aplica la entrada y vuelve a intentar.');
        }

        return redirect()
            ->route('huentitan.salidas.show', $salida)
            ->with('status', 'La aplicacion al inventario se construira en SO-6.');
    }

    public function cancelar(HuentitanSalida $salida)
    {
        return redirect()
            ->route('huentitan.salidas.show', $salida)
            ->with('status', 'La cancelacion de salidas queda preparada para SO-8.');
    }

    private function estadoPartidasActuales(HuentitanSalida $salida, Almacen $almacen)
    {
        return $salida->detalles
            ->map(function (HuentitanSalidaDetalle $detalle) use ($almacen) {
                $stock = InventarioStock::query()
                    ->where('almacen_id', $almacen->id)
                    ->where('producto_id', $detalle->producto_id)
                    ->first();

                $disponible = max(0, (float) ($stock->stock_actual ?? 0) - (float) ($stock->stock_reservado ?? 0));
                $faltante = max(0, (float) $detalle->cantidad_salida - $disponible);

                return [
                    'detalle' => $detalle,
                    'disponible' => $disponible,
                    'faltante' => $faltante,
                    'completo' => $faltante <= 0,
                ];
            })
            ->keyBy(fn ($item) => $item['detalle']->id);
    }
    private function generarFolioSalida(): string
    {
        $prefijo = 'SAL-HUE-' . now()->format('Ym') . '-';
        $ultimoFolio = HuentitanSalida::query()
            ->where('folio', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('folio')
            ->value('folio');

        $consecutivo = $ultimoFolio ? ((int) substr($ultimoFolio, -4)) + 1 : 1;

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
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










