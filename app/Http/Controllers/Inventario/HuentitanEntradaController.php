<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Area;
use App\Models\HuentitanEntrada;
use App\Models\HuentitanEntradaDetalle;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HuentitanEntradaController extends Controller
{
    private const ALMACEN_CODIGO = 'AL-HUENTITAN';

    public function index(Request $request)
    {
        $almacen = $this->almacenHuentitan();
        $busqueda = trim((string) $request->query('q', ''));
        $estado = $request->query('estado', 'todos');
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');

        $query = HuentitanEntrada::query()
            ->with(['almacen', 'ordenCompra.proveedor', 'usuario', 'detalles.producto'])
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
                    ->orWhereHas('ordenCompra', function ($ocQuery) use ($busqueda) {
                        $ocQuery->where('folio', 'like', "%{$busqueda}%")
                            ->orWhereHas('proveedor', function ($provQuery) use ($busqueda) {
                                $provQuery->where('nombre', 'like', "%{$busqueda}%");
                            });
                    });
            });
        }

        $resumen = [
            'total' => HuentitanEntrada::query()->where('almacen_id', $almacen->id)->count(),
            'aplicadas' => HuentitanEntrada::query()->where('almacen_id', $almacen->id)->where('estado', 'aplicada')->count(),
            'borrador' => HuentitanEntrada::query()->where('almacen_id', $almacen->id)->where('estado', 'borrador')->count(),
            'canceladas' => HuentitanEntrada::query()->where('almacen_id', $almacen->id)->where('estado', 'cancelada')->count(),
        ];

        $entradas = $query->paginate(20)->withQueryString();

        return view('huentitan.entradas.index', compact('almacen', 'entradas', 'busqueda', 'estado', 'fechaDesde', 'fechaHasta', 'resumen'));
    }

    public function create(Request $request)
    {
        $almacen = $this->almacenHuentitan();
        $area = $this->areaHuentitan($almacen);

        // Órdenes de compra autorizadas con saldo pendiente de recepción
        $ordenesCompra = OrdenCompra::query()
            ->with(['proveedor', 'detalles.producto'])
            ->where('estado', 'AUTORIZADA')
            ->whereIn('estado_recepcion', ['pendiente', 'parcial'])
            ->whereHas('detalles', function ($dQuery) {
                $dQuery->whereRaw('cantidad > cantidad_recibida');
            })
            ->when($area, function ($q) use ($area) {
                $q->where(function ($sub) use ($area) {
                    $sub->where('area_id', $area->id)
                        ->orWhere('area', 'like', '%HUENTITAN%')
                        ->orWhere('area', 'like', '%HUNTITAN%')
                        ->orWhere('area', 'HT');
                });
            })
            ->latest('fecha')
            ->get();

        $selectedOcId = (int) $request->query('orden_compra_id', 0);

        return view('huentitan.entradas.create', compact('almacen', 'ordenesCompra', 'selectedOcId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'orden_compra_id' => 'required|exists:ordenes_compra,id',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string|max:1000',
            'partidas' => 'required|array|min:1',
            'partidas.*.orden_compra_detalle_id' => 'required|exists:orden_compra_detalles,id',
            'partidas.*.cantidad_recibida' => 'required|numeric|gt:0',
            'partidas.*.observaciones' => 'nullable|string|max:500',
        ], [
            'orden_compra_id.required' => 'Debes seleccionar una orden de compra.',
            'partidas.required' => 'Debes incluir al menos una partida para recibir.',
            'partidas.*.cantidad_recibida.gt' => 'La cantidad recibida debe ser mayor a cero.',
        ]);

        $almacen = $this->almacenHuentitan();
        $area = $this->areaHuentitan($almacen);

        $ordenCompra = OrdenCompra::with('detalles.producto')->findOrFail($request->orden_compra_id);

        $validacionOrden = $this->validarOrdenCompraRecepcion($ordenCompra, $area);
        if ($validacionOrden) {
            return back()->withInput()->with('error', $validacionOrden);
        }

        // Filtrar partidas seleccionadas con cantidad mayor a cero.
        $partidasInput = collect($request->partidas)
            ->filter(fn ($p) => isset($p['cantidad_recibida']) && (float) $p['cantidad_recibida'] > 0)
            ->keyBy(fn ($p) => (int) $p['orden_compra_detalle_id']);

        if ($partidasInput->isEmpty()) {
            return back()->withInput()->with('error', 'Debes recibir al menos un producto con cantidad mayor a cero.');
        }

        $errorPartidas = $this->validarPartidasRecepcion($ordenCompra, $partidasInput);
        if ($errorPartidas) {
            return back()->withInput()->with('error', $errorPartidas);
        }

        $entrada = DB::transaction(function () use ($almacen, $area, $ordenCompra, $request, $partidasInput) {
            $ordenCompra = OrdenCompra::query()
                ->with('detalles.producto')
                ->whereKey($ordenCompra->id)
                ->lockForUpdate()
                ->firstOrFail();

            $validacionOrden = $this->validarOrdenCompraRecepcion($ordenCompra, $area);
            if ($validacionOrden) {
                throw new \RuntimeException($validacionOrden);
            }

            $errorPartidas = $this->validarPartidasRecepcion($ordenCompra, $partidasInput);
            if ($errorPartidas) {
                throw new \RuntimeException($errorPartidas);
            }

            $folio = $this->generarFolioEntrada();

            $entrada = HuentitanEntrada::create([
                'folio' => $folio,
                'almacen_id' => $almacen->id,
                'orden_compra_id' => $ordenCompra->id,
                'tipo_origen' => 'orden_compra',
                'fecha' => $request->fecha ? Carbon::parse($request->fecha) : now(),
                'estado' => 'borrador',
                'usuario_id' => auth()->id(),
                'observaciones' => $request->observaciones,
            ]);

            foreach ($partidasInput as $p) {
                $ocDetalle = $ordenCompra->detalles->find((int) $p['orden_compra_detalle_id']);
                $cantRecibida = (float) $p['cantidad_recibida'];
                $costoUnitario = (float) $ocDetalle->precio_unitario;
                $importe = round($cantRecibida * $costoUnitario, 2);

                $entrada->detalles()->create([
                    'orden_compra_detalle_id' => $ocDetalle->id,
                    'producto_id' => $ocDetalle->producto_id,
                    'descripcion' => $ocDetalle->descripcion ?: $ocDetalle->producto?->nombre,
                    'unidad' => $ocDetalle->unidad ?: ($ocDetalle->producto?->unidad ?: 'PZA'),
                    'cantidad_ordenada' => (float) $ocDetalle->cantidad,
                    'cantidad_recibida' => $cantRecibida,
                    'costo_unitario' => $costoUnitario,
                    'importe' => $importe,
                    'observaciones' => $p['observaciones'] ?? null,
                ]);
            }

            return $entrada;
        });

        return redirect()
            ->route('huentitan.entradas.show', $entrada)
            ->with('status', "Entrada {$entrada->folio} guardada exitosamente en estado borrador.");
    }

    private function validarOrdenCompraRecepcion(OrdenCompra $ordenCompra, ?Area $area): ?string
    {
        $ordenCompra->loadMissing('detalles.producto');

        if (strtoupper((string) $ordenCompra->estado) !== 'AUTORIZADA') {
            return 'La orden de compra no se encuentra autorizada.';
        }

        if (! in_array((string) ($ordenCompra->estado_recepcion ?: 'pendiente'), ['pendiente', 'parcial'], true)) {
            return 'La orden de compra no tiene saldo pendiente de recepcion.';
        }

        if (! $this->ordenCompraPerteneceHuentitan($ordenCompra, $area)) {
            return 'La orden de compra seleccionada no pertenece al area HUENTITAN.';
        }

        $tienePendientes = $ordenCompra->detalles->contains(function (OrdenCompraDetalle $detalle) {
            return ((float) $detalle->cantidad - (float) $detalle->cantidad_recibida) > 0;
        });

        return $tienePendientes ? null : 'La orden de compra no tiene partidas pendientes de recibir.';
    }

    private function validarPartidasRecepcion(OrdenCompra $ordenCompra, $partidasInput): ?string
    {
        $ordenCompra->loadMissing('detalles.producto');

        foreach ($partidasInput as $p) {
            $detalleId = (int) ($p['orden_compra_detalle_id'] ?? 0);
            $ocDetalle = $ordenCompra->detalles->find($detalleId);

            if (! $ocDetalle) {
                return 'Una de las partidas seleccionadas no pertenece a la orden de compra.';
            }

            $pendiente = max(0, (float) $ocDetalle->cantidad - (float) $ocDetalle->cantidad_recibida);
            $cantRecibir = (float) ($p['cantidad_recibida'] ?? 0);

            if ($cantRecibir <= 0) {
                return 'La cantidad recibida debe ser mayor a cero.';
            }

            if ($cantRecibir > $pendiente) {
                $nombreProd = $ocDetalle->producto?->nombre ?? $ocDetalle->descripcion;
                return "La cantidad a recibir de '{$nombreProd}' ({$cantRecibir}) excede el saldo pendiente ({$pendiente}).";
            }
        }

        return null;
    }

    private function ordenCompraPerteneceHuentitan(OrdenCompra $ordenCompra, ?Area $area): bool
    {
        if ($area && (int) $ordenCompra->area_id === (int) $area->id) {
            return true;
        }

        $areaTexto = strtoupper(trim((string) $ordenCompra->area));

        return $areaTexto === 'HT'
            || str_contains($areaTexto, 'HUENTITAN')
            || str_contains($areaTexto, 'HUNTITAN');
    }
    private function generarFolioEntrada(): string
    {
        $prefijo = 'ENT-HUE-' . now()->format('Ym') . '-';
        $ultimoFolio = HuentitanEntrada::query()
            ->where('folio', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('folio')
            ->value('folio');

        $consecutivo = $ultimoFolio ? ((int) substr($ultimoFolio, -4)) + 1 : 1;

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    public function show(HuentitanEntrada $entrada)
    {
        $almacen = $this->almacenHuentitan();
        $entrada->load(['almacen', 'ordenCompra.proveedor', 'usuario', 'aplicadaPor', 'canceladaPor', 'detalles.producto', 'detalles.ordenCompraDetalle']);

        return view('huentitan.entradas.show', compact('almacen', 'entrada'));
    }

    public function aplicar(HuentitanEntrada $entrada)
    {
        try {
            DB::transaction(function () use ($entrada) {
                $entrada = HuentitanEntrada::query()
                    ->with(['detalles.ordenCompraDetalle', 'ordenCompra.detalles'])
                    ->whereKey($entrada->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $entrada->isBorrador()) {
                    throw new \RuntimeException('Solo se pueden aplicar entradas en estado borrador.');
                }

                if (! $entrada->ordenCompra) {
                    throw new \RuntimeException('La entrada no tiene una orden de compra origen.');
                }

                if ($entrada->detalles->isEmpty()) {
                    throw new \RuntimeException('La entrada no tiene productos para aplicar.');
                }

                foreach ($entrada->detalles as $detalle) {
                    $cantidad = (float) $detalle->cantidad_recibida;
                    $costoUnitario = (float) $detalle->costo_unitario;

                    if (! $detalle->producto_id) {
                        throw new \RuntimeException("La partida {$detalle->id} no tiene producto ligado al catalogo.");
                    }

                    if ($cantidad <= 0) {
                        throw new \RuntimeException("La partida {$detalle->id} tiene una cantidad recibida invalida.");
                    }

                    $ocDetalle = OrdenCompraDetalle::query()
                        ->whereKey($detalle->orden_compra_detalle_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $ocDetalle) {
                        throw new \RuntimeException("La partida {$detalle->id} no tiene detalle de orden de compra ligado.");
                    }

                    $pendiente = max(0, (float) $ocDetalle->cantidad - (float) $ocDetalle->cantidad_recibida);
                    if ($cantidad > $pendiente) {
                        $nombre = $detalle->producto?->nombre ?? $detalle->descripcion ?? "partida {$detalle->id}";
                        throw new \RuntimeException("La cantidad a aplicar de '{$nombre}' ({$cantidad}) excede el pendiente de la OC ({$pendiente}).");
                    }

                    $stockRow = DB::table('inventario_stock')
                        ->where('almacen_id', $entrada->almacen_id)
                        ->where('producto_id', $detalle->producto_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stockRow) {
                        DB::table('inventario_stock')->insert([
                            'almacen_id' => $entrada->almacen_id,
                            'producto_id' => $detalle->producto_id,
                            'stock_actual' => 0,
                            'stock_reservado' => 0,
                            'valor_total' => 0,
                            'costo_promedio' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $stockActual = 0.0;
                        $valorTotal = 0.0;
                    } else {
                        $stockActual = (float) $stockRow->stock_actual;
                        $valorTotal = (float) ($stockRow->valor_total ?? 0);
                    }

                    $nuevoStock = $stockActual + $cantidad;
                    $nuevoValor = $valorTotal + ($cantidad * $costoUnitario);
                    $nuevoCostoPromedio = $nuevoStock > 0 ? $nuevoValor / $nuevoStock : 0;

                    DB::table('inventario_stock')
                        ->where('almacen_id', $entrada->almacen_id)
                        ->where('producto_id', $detalle->producto_id)
                        ->update([
                            'stock_actual' => $nuevoStock,
                            'valor_total' => $nuevoValor,
                            'costo_promedio' => $nuevoCostoPromedio,
                            'updated_at' => now(),
                        ]);

                    DB::table('inventario_movimientos')->insert([
                        'almacen_id' => $entrada->almacen_id,
                        'producto_id' => $detalle->producto_id,
                        'documento_id' => $entrada->id,
                        'fecha' => $entrada->fecha ?? now(),
                        'tipo_movimiento' => 'in',
                        'cantidad' => $cantidad,
                        'costo_unitario' => $costoUnitario,
                        'saldo_cantidad' => $nuevoStock,
                        'obra_id' => $entrada->ordenCompra?->obra_id,
                        'residente_id' => null,
                        'creado_por' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $ocDetalle->cantidad_recibida = (float) $ocDetalle->cantidad_recibida + $cantidad;
                    $ocDetalle->save();

                    $this->registrarProveedorProductoDesdeEntrada($entrada, $detalle, $ocDetalle);
                }

                $this->actualizarEstadoRecepcionOrdenCompra($entrada->ordenCompra);

                $entrada->update([
                    'estado' => 'aplicada',
                    'aplicada_por' => auth()->id(),
                    'fecha_aplicacion' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('huentitan.entradas.show', $entrada)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('huentitan.entradas.show', $entrada)
            ->with('status', 'Entrada aplicada al inventario correctamente.');
    }

    private function registrarProveedorProductoDesdeEntrada(
        HuentitanEntrada $entrada,
        HuentitanEntradaDetalle $detalle,
        OrdenCompraDetalle $ocDetalle
    ): void {
        $proveedorId = (int) ($entrada->ordenCompra?->proveedor_id ?? 0);
        $productoId = (int) ($detalle->producto_id ?? $ocDetalle->producto_id ?? 0);

        if (! $proveedorId || ! $productoId) {
            return;
        }

        $precio = (float) ($detalle->costo_unitario ?? $ocDetalle->precio_unitario ?? 0);
        $moneda = (string) ($entrada->ordenCompra?->moneda ?? 'MXN');
        $now = now();

        DB::table('producto_proveedor')->updateOrInsert(
            [
                'producto_id' => $productoId,
                'proveedor_id' => $proveedorId,
            ],
            [
                'precio_lista' => $precio,
                'moneda' => $moneda,
                'activo' => 1,
                'notas' => 'Actualizado desde entrada HUENTITAN ' . $entrada->folio,
                'updated_at' => $now,
            ]
        );

        $pivot = DB::table('producto_proveedor')
            ->where('producto_id', $productoId)
            ->where('proveedor_id', $proveedorId)
            ->first();

        if ($pivot && ! $pivot->created_at) {
            DB::table('producto_proveedor')
                ->where('id', $pivot->id)
                ->update(['created_at' => $now]);
        }

        if (! Schema::hasTable('producto_proveedor_precios')) {
            return;
        }

        $yaRegistrado = DB::table('producto_proveedor_precios')
            ->where('producto_id', $productoId)
            ->where('proveedor_id', $proveedorId)
            ->where('orden_compra_id', $entrada->orden_compra_id)
            ->where('precio', $precio)
            ->where('moneda', $moneda)
            ->exists();

        if ($yaRegistrado) {
            return;
        }

        DB::table('producto_proveedor_precios')->insert([
            'producto_id' => $productoId,
            'proveedor_id' => $proveedorId,
            'precio' => $precio,
            'moneda' => $moneda,
            'orden_compra_id' => $entrada->orden_compra_id,
            'created_at' => $entrada->fecha_aplicacion ?? $now,
            'updated_at' => $now,
        ]);
    }
    private function actualizarEstadoRecepcionOrdenCompra(OrdenCompra $ordenCompra): void
    {
        $detalles = $ordenCompra->detalles()->lockForUpdate()->get();

        $cantidadOrdenada = (float) $detalles->sum(fn (OrdenCompraDetalle $detalle) => (float) $detalle->cantidad);
        $cantidadRecibida = (float) $detalles->sum(fn (OrdenCompraDetalle $detalle) => (float) $detalle->cantidad_recibida);

        if ($cantidadRecibida <= 0) {
            $estadoRecepcion = 'pendiente';
        } elseif ($cantidadRecibida + 0.0005 >= $cantidadOrdenada) {
            $estadoRecepcion = 'recibida';
        } else {
            $estadoRecepcion = 'parcial';
        }

        $ordenCompra->forceFill([
            'estado_recepcion' => $estadoRecepcion,
        ])->save();
    }
    public function cancelar(Request $request, HuentitanEntrada $entrada)
    {
        // Se implementará en la Fase 6
        return redirect()->route('huentitan.entradas.show', $entrada);
    }

    public function ordenCompraDetalles(OrdenCompra $ordenCompra)
    {
        $almacen = $this->almacenHuentitan();
        $area = $this->areaHuentitan($almacen);
        $ordenCompra->load('detalles.producto');

        $validacionOrden = $this->validarOrdenCompraRecepcion($ordenCompra, $area);
        if ($validacionOrden) {
            return response()->json([
                'message' => $validacionOrden,
            ], 422);
        }

        $detalles = $ordenCompra->detalles()
            ->with('producto')
            ->get()
            ->map(function (OrdenCompraDetalle $d) {
                $pendiente = max(0, (float) $d->cantidad - (float) $d->cantidad_recibida);
                return [
                    'id' => $d->id,
                    'producto_id' => $d->producto_id,
                    'producto_sku' => $d->producto?->sku ?? '',
                    'producto_nombre' => $d->producto?->nombre ?? $d->descripcion,
                    'descripcion' => $d->descripcion,
                    'unidad' => $d->unidad ?: ($d->producto?->unidad ?: 'PZA'),
                    'cantidad_ordenada' => (float) $d->cantidad,
                    'cantidad_recibida_previa' => (float) $d->cantidad_recibida,
                    'cantidad_pendiente' => $pendiente,
                    'cantidad_a_recibir' => $pendiente,
                    'costo_unitario' => (float) $d->precio_unitario,
                    'importe' => round($pendiente * (float) $d->precio_unitario, 2),
                ];
            })
            ->filter(fn ($item) => $item['cantidad_pendiente'] > 0)
            ->values();

        return response()->json([
            'orden_compra' => [
                'id' => $ordenCompra->id,
                'folio' => $ordenCompra->folio,
                'proveedor' => $ordenCompra->proveedor?->nombre ?? 'Sin proveedor',
                'proveedor_rfc' => $ordenCompra->proveedor?->rfc ?? '',
                'fecha' => $ordenCompra->fecha ? $ordenCompra->fecha->format('d/m/Y') : '-',
                'total' => (float) $ordenCompra->total,
            ],
            'detalles' => $detalles,
        ]);
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
}





