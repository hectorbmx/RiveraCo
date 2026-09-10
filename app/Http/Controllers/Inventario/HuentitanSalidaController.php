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

        $salida->load([
            'obra',
            'usuario',
            'aplicadaPor',
            'canceladaPor',
            'detalles.producto',
            'detalles.huentitanEntradaDetalles.entrada',
            'detalles.ordenCompraDetalles.orden',
            'detalles.ordenCompraDetalles.huentitanEntradaDetalles.entrada',
        ]);
        $estadoPartidas = $this->estadoPartidasActuales($salida, $almacen);
        $faltantesActuales = $estadoPartidas->filter(fn ($item) => $item['faltante'] > 0)->values();
        $estadoOperativo = $salida->isBorrador()
            ? ($faltantesActuales->isEmpty() ? 'listo_para_aplicar' : 'con_faltantes')
            : $salida->estado;

        return response()->view('huentitan.salidas.show', compact('almacen', 'salida', 'faltantesActuales', 'estadoPartidas', 'estadoOperativo'));
    }

    public function imprimir(HuentitanSalida $salida)
    {
        $almacen = $this->almacenHuentitan();
        abort_unless((int) $salida->almacen_id === (int) $almacen->id, 404);

        $salida->load([
            'obra',
            'usuario',
            'aplicadaPor',
            'detalles.producto',
        ]);

        return response()->view('huentitan.salidas.print', compact('almacen', 'salida'));
    }
    public function aplicar(HuentitanSalida $salida)
    {
        $almacen = $this->almacenHuentitan();
        abort_unless((int) $salida->almacen_id === (int) $almacen->id, 404);

        try {
            DB::transaction(function () use ($salida, $almacen) {
                $salida = HuentitanSalida::query()
                    ->with('detalles.producto')
                    ->whereKey($salida->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $salida->almacen_id !== (int) $almacen->id) {
                    throw new \RuntimeException('La salida no pertenece al almacen HUENTITAN.');
                }

                if (! $salida->isBorrador()) {
                    throw new \RuntimeException('Solo se pueden aplicar salidas en estado borrador.');
                }

                if ($salida->detalles->isEmpty()) {
                    throw new \RuntimeException('La salida no tiene productos para aplicar.');
                }

                foreach ($salida->detalles as $detalle) {
                    $cantidad = round((float) $detalle->cantidad_salida, 3);

                    if ($cantidad <= 0) {
                        throw new \RuntimeException("La partida {$detalle->id} tiene una cantidad invalida.");
                    }

                    if (! $detalle->producto_id) {
                        throw new \RuntimeException("La partida {$detalle->id} no tiene producto ligado al catalogo.");
                    }

                    $stockRow = DB::table('inventario_stock')
                        ->where('almacen_id', $salida->almacen_id)
                        ->where('producto_id', $detalle->producto_id)
                        ->lockForUpdate()
                        ->first();

                    $stockActual = (float) ($stockRow->stock_actual ?? 0);
                    $stockReservado = (float) ($stockRow->stock_reservado ?? 0);
                    $stockDisponible = max(0, $stockActual - $stockReservado);

                    if (! $stockRow || $cantidad > $stockDisponible) {
                        $nombre = $detalle->producto?->nombre ?? $detalle->descripcion ?? "partida {$detalle->id}";
                        throw new \RuntimeException("Stock insuficiente para '{$nombre}'. Disponible: {$stockDisponible}, requiere: {$cantidad}.");
                    }

                    $valorTotal = (float) ($stockRow->valor_total ?? 0);
                    $costoPromedio = (float) ($stockRow->costo_promedio ?? 0);
                    $nuevoStock = $stockActual - $cantidad;
                    $nuevoValor = max(0, $valorTotal - ($cantidad * $costoPromedio));
                    $nuevoCostoPromedio = $nuevoStock > 0 ? ($nuevoValor / $nuevoStock) : 0;

                    DB::table('inventario_stock')
                        ->where('almacen_id', $salida->almacen_id)
                        ->where('producto_id', $detalle->producto_id)
                        ->update([
                            'stock_actual' => $nuevoStock,
                            'valor_total' => $nuevoValor,
                            'costo_promedio' => $nuevoCostoPromedio,
                            'updated_at' => now(),
                        ]);

                    DB::table('inventario_movimientos')->insert([
                        'almacen_id' => $salida->almacen_id,
                        'producto_id' => $detalle->producto_id,
                        'documento_id' => $salida->id,
                        'fecha' => $salida->fecha ?? now(),
                        'tipo_movimiento' => 'out',
                        'cantidad' => $cantidad,
                        'costo_unitario' => $costoPromedio,
                        'saldo_cantidad' => $nuevoStock,
                        'obra_id' => $salida->obra_id,
                        'residente_id' => null,
                        'creado_por' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $detalle->forceFill([
                        'costo_unitario' => $costoPromedio,
                        'importe' => round($cantidad * $costoPromedio, 2),
                    ])->save();
                }

                $salida->forceFill([
                    'estado' => 'aplicada',
                    'aplicada_por' => auth()->id(),
                    'fecha_aplicacion' => now(),
                ])->save();
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('huentitan.salidas.show', $salida)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('huentitan.salidas.show', $salida)
            ->with('status', 'Salida aplicada al inventario correctamente.');
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
                $ordenCompraDetalles = $detalle->relationLoaded('ordenCompraDetalles')
                    ? $detalle->ordenCompraDetalles
                    : collect();
                $entradaDetallesDirectos = $detalle->relationLoaded('huentitanEntradaDetalles')
                    ? $detalle->huentitanEntradaDetalles
                    : collect();
                $entradaDetallesPorOc = $ordenCompraDetalles->flatMap(function ($ordenDetalle) {
                    return $ordenDetalle->relationLoaded('huentitanEntradaDetalles')
                        ? $ordenDetalle->huentitanEntradaDetalles
                        : collect();
                });
                $entradaDetalles = $entradaDetallesDirectos
                    ->concat($entradaDetallesPorOc)
                    ->unique('id')
                    ->values();
                $entradaDetallesAplicados = $entradaDetalles->filter(fn ($entradaDetalle) => $entradaDetalle->entrada?->estado === 'aplicada');
                $entradasAplicadas = $entradaDetallesAplicados
                    ->pluck('entrada')
                    ->filter()
                    ->unique('id')
                    ->values();
                $entradasBorrador = $entradaDetalles
                    ->filter(fn ($entradaDetalle) => $entradaDetalle->entrada?->estado === 'borrador')
                    ->pluck('entrada')
                    ->filter()
                    ->unique('id')
                    ->values();
                $ordenesCompra = $ordenCompraDetalles
                    ->pluck('orden')
                    ->filter()
                    ->unique('id')
                    ->values();
                $cantidadNecesariaCompra = (float) ($detalle->cantidad_sugerida_compra ?: $detalle->cantidad_faltante ?: 0);
                $cantidadComprada = (float) $ordenCompraDetalles->sum(fn ($ordenDetalle) => (float) $ordenDetalle->cantidad);
                $cantidadRecibidaAplicada = (float) $entradaDetallesAplicados->sum(fn ($entradaDetalle) => (float) $entradaDetalle->cantidad_recibida);
                $cantidadPendienteCompra = max(0, $cantidadNecesariaCompra - $cantidadComprada);
                $cantidadPendienteRecibir = max(0, $cantidadComprada - $cantidadRecibidaAplicada);

                $estadoCompra = 'sin_compra';
                if ($cantidadRecibidaAplicada > 0 && $cantidadRecibidaAplicada + 0.0005 >= $cantidadNecesariaCompra) {
                    $estadoCompra = 'entrada_completa';
                } elseif ($cantidadRecibidaAplicada > 0) {
                    $estadoCompra = 'entrada_parcial';
                } elseif ($entradasBorrador->isNotEmpty()) {
                    $estadoCompra = 'entrada_borrador';
                } elseif ($cantidadComprada > 0 && $cantidadComprada + 0.0005 >= $cantidadNecesariaCompra) {
                    $estadoCompra = 'oc_completa';
                } elseif ($cantidadComprada > 0) {
                    $estadoCompra = 'oc_parcial';
                } elseif ($ordenesCompra->isNotEmpty()) {
                    $estadoCompra = 'oc_generada';
                }

                return [
                    'detalle' => $detalle,
                    'disponible' => $disponible,
                    'faltante' => $faltante,
                    'completo' => $faltante <= 0,
                    'compra' => [
                        'estado' => $estadoCompra,
                        'ordenes' => $ordenesCompra,
                        'entradas_borrador' => $entradasBorrador,
                        'entradas_aplicadas' => $entradasAplicadas,
                        'cantidad_necesaria_compra' => $cantidadNecesariaCompra,
                        'cantidad_comprada' => $cantidadComprada,
                        'cantidad_recibida_aplicada' => $cantidadRecibidaAplicada,
                        'cantidad_pendiente_compra' => $cantidadPendienteCompra,
                        'cantidad_pendiente_recibir' => $cantidadPendienteRecibir,
                    ],
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

















