<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;

use App\Models\InventarioDocumento;
use App\Models\Almacen;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\OrdenCompra;
use App\Models\Obra;
use App\Models\Vehiculo;
use App\Models\Maquina;
use App\Models\ReposicionCajaChicaGasto;
use App\Models\MantenimientoAbastecimiento;
use App\Models\InventarioDocumentoDetalle;
use App\Services\Inventario\InventarioDocumentoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioDocumentoController extends Controller
{
    
 public function index(Request $request)
    {
        $almacenId = $request->integer('almacen_id');
        $tipo      = trim((string) $request->get('tipo', ''));
        $estado    = trim((string) $request->get('estado', '')); // borrador|aplicado|cancelado (según tu modelo)
        $q         = trim((string) $request->get('q', ''));

        $docs = InventarioDocumento::query()
            ->with(['almacen', 'detalles.ordenCompra', 'mantenimientoAbastecimiento.mantenimiento.maquina'])
            ->when($almacenId, fn($qq) => $qq->where('almacen_id', $almacenId))
            ->when($tipo !== '', fn($qq) => $qq->where('tipo', $tipo))
            ->when($estado !== '', fn($qq) => $qq->where('estado', $estado))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('folio', 'like', "%{$q}%")
                      ->orWhere('referencia', 'like', "%{$q}%")
                      ->orWhere('observaciones', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $almacenes = Almacen::query()->orderBy('nombre')->get(['id','nombre']);

        // Ajusta estos tipos a lo que tú uses (entrada/salida/cancelacion/ajuste/etc.)
        $tipos = [
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'cancelacion' => 'Cancelación',
        ];

        // Ajusta a tu enum real
        $estados = [
            'borrador' => 'Borrador',
            'aplicado' => 'Aplicado',
            'cancelado' => 'Cancelado',
        ];

        return view('inventario.documentos.index', compact('docs','almacenes','almacenId','tipo','estado','q','tipos','estados'));
    }
public function create(Request $request)
{
    $tipo = $request->query('tipo', 'entrada');
    $almacenFijo = in_array($tipo, ['entrada', 'salida'], true) ? $this->almacenGiralda() : null;
    $abastecimiento = null;
    $partidasIniciales = [];
    $stockPorAlmacen = [];

    if ($tipo === 'salida' && $request->filled('mantenimiento_abastecimiento_id')) {
        $abastecimiento = MantenimientoAbastecimiento::query()
            ->with(['mantenimiento.maquina', 'mantenimiento.vehiculo', 'detalles.producto'])
            ->whereKey($request->integer('mantenimiento_abastecimiento_id'))
            ->where(function ($query) {
                $query->where(function ($directo) {
                    $directo->where('tipo', MantenimientoAbastecimiento::TIPO_ALMACEN)
                        ->whereNotIn('estado', ['surtida', 'cancelada']);
                })->orWhere(function ($compra) {
                    $compra->where('tipo', MantenimientoAbastecimiento::TIPO_COMPRA)
                        ->whereIn('estado', ['recibida_parcial', 'material_en_almacen', 'parcial']);
                });
            })
            ->firstOrFail();

        $partidasIniciales = $abastecimiento->detalles
            ->map(function ($detalle) use ($abastecimiento) {
                $pendiente = max(0, (float) $detalle->cantidad_solicitada - (float) $detalle->cantidad_atendida);
                $mantenimiento = $abastecimiento->mantenimiento;
                $maquinaId = $mantenimiento?->maquina_id;
                $vehiculoId = $mantenimiento?->vehiculo_id;

                return [
                    'producto_id' => $detalle->producto_id,
                    'nombre' => $detalle->producto_nombre_snapshot ?: ($detalle->producto?->nombre ?? 'Producto'),
                    'cantidad' => $pendiente,
                    'cantidad_pendiente' => $pendiente,
                    'disponible' => null,
                    'costo_unitario' => 0,
                    'notas' => 'Solicitud ' . $detalle->abastecimiento?->folio,
                    'equipo_activo' => (bool) ($maquinaId || $vehiculoId),
                    'equipo_tipo' => $maquinaId ? 'maquina' : ($vehiculoId ? 'vehiculo' : ''),
                    'vehiculo_id' => $vehiculoId ?: '',
                    'maquina_id' => $maquinaId ?: '',
                    'mantenimiento_abastecimiento_detalle_id' => $detalle->id,
                ];
            })
            ->filter(fn (array $detalle) => $detalle['cantidad'] > 0)
            ->values()
            ->all();

        $productoIds = collect($partidasIniciales)
            ->pluck('producto_id')
            ->filter()
            ->unique()
            ->values();

        if ($productoIds->isNotEmpty()) {
            $stockPorAlmacen = \App\Models\InventarioStock::query()
                ->whereIn('producto_id', $productoIds)
                ->get(['almacen_id', 'producto_id', 'stock_actual'])
                ->groupBy('almacen_id')
                ->map(fn ($stocks) => $stocks->mapWithKeys(
                    fn ($stock) => [(string) $stock->producto_id => (float) $stock->stock_actual]
                ))
                ->toArray();
        }
    }

    $almacenes = Almacen::query()
        ->where('activo', true)
        ->when($almacenFijo, fn ($query) => $query->whereKey($almacenFijo->id))
        ->orderBy('nombre')
        ->get(['id', 'nombre']);

    $obras = Obra::query()
        ->when(
            $tipo === 'salida',
            fn ($q) => $q->where('estatus_nuevo', Obra::ESTATUS_EJECUCION),
            fn ($q) => $q->where('status', 'planeacion')
        )
        ->orderBy('clave_obra')
        ->get(['id', 'clave_obra', 'nombre', 'estatus_nuevo']);

    $proveedores = Proveedor::query()
        ->where('activo', true)
        ->orderBy('nombre')
        ->get(['id', 'rfc', 'nombre']);

    $ordenesCompra = collect();
    $vehiculos = collect();
    $maquinas = collect();
    $solicitudesAlmacenPendientes = collect();

    if ($tipo === 'salida') {
        // TODO: filtrar en una siguiente fase por los equipos asignados a la obra seleccionada.
        $vehiculos = Vehiculo::query()
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get(['id', 'marca', 'modelo', 'placas', 'tipo']);

        $maquinas = Maquina::query()
            ->orderBy('codigo')
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'tipo', 'marca', 'modelo', 'estado']);

        $solicitudesAlmacenPendientes = MantenimientoAbastecimiento::query()
            ->with(['mantenimiento.maquina', 'mantenimiento.vehiculo', 'mantenimiento.servicioPreventivoTipo'])
            ->where('tipo', MantenimientoAbastecimiento::TIPO_ALMACEN)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereHas('detalles', fn ($query) => $query
                ->whereColumn('cantidad_solicitada', '>', 'cantidad_atendida'))
            ->orderBy('id')
            ->get();
    }

// En tu Controller
if ($tipo === 'entrada') {
    $ordenesCompra = OrdenCompra::query()
        ->with('proveedor')
        ->whereIn('estado', ['AUTORIZADA', 'APROBADA']) // Ajusta según tus datos reales
        ->whereIn('estado_recepcion', ['pendiente', 'parcial'])
        ->whereHas('detalles', fn ($query) => $query->whereColumn('cantidad_recibida', '<', 'cantidad'))
        ->orderByDesc('id')
        ->get(['id', 'folio', 'proveedor_id', 'estado']);
}

    $tipos = [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
        'cancelacion' => 'Cancelación',
    ];

    return view('inventario.documentos.create', compact(
        'tipo',
        'almacenes',
        'almacenFijo',
        'tipos',
        'obras',
        'proveedores',
        'ordenesCompra',
        'vehiculos',
        'maquinas',
        'abastecimiento',
        'partidasIniciales',
        'stockPorAlmacen',
        'solicitudesAlmacenPendientes'
    ));
}

/**
 * Solicitudes de mantenimiento pendientes de surtir desde almacén.
 * Se mantiene separado de la creación de documentos para reutilizarlo
 * en el badge del catálogo y en el selector de nuevas salidas.
 */
public function solicitudesAlmacenPendientes()
{
    $solicitudes = MantenimientoAbastecimiento::query()
        ->with([
            'mantenimiento.maquina',
            'mantenimiento.vehiculo',
            'mantenimiento.servicioPreventivoTipo',
            'detalles' => fn ($query) => $query
                ->whereColumn('cantidad_solicitada', '>', 'cantidad_atendida')
                ->orderBy('id'),
        ])
        ->where('tipo', MantenimientoAbastecimiento::TIPO_ALMACEN)
        ->whereIn('estado', ['pendiente', 'parcial'])
        ->whereHas('detalles', fn ($query) => $query
            ->whereColumn('cantidad_solicitada', '>', 'cantidad_atendida'))
        ->orderBy('id')
        ->get()
        ->map(function (MantenimientoAbastecimiento $solicitud) {
            $mantenimiento = $solicitud->mantenimiento;
            $equipo = $mantenimiento?->maquina?->codigo
                ?? $mantenimiento?->maquina?->nombre
                ?? $mantenimiento?->vehiculo?->placas
                ?? $mantenimiento?->vehiculo?->modelo;

            return [
                'id' => $solicitud->id,
                'folio' => $solicitud->folio,
                'estado' => $solicitud->estado,
                'mantenimiento_id' => $mantenimiento?->id,
                'equipo' => $equipo,
                'servicio' => $mantenimiento?->servicioPreventivoTipo?->nombre
                    ?? $mantenimiento?->categoria_mantenimiento,
                'partidas_pendientes' => $solicitud->detalles->map(fn ($detalle) => [
                    'producto_id' => $detalle->producto_id,
                    'nombre' => $detalle->producto_nombre_snapshot,
                    'sku' => $detalle->producto_sku_snapshot,
                    'unidad' => $detalle->unidad,
                    'cantidad_pendiente' => max(
                        0,
                        (float) $detalle->cantidad_solicitada - (float) $detalle->cantidad_atendida
                    ),
                ])->values(),
            ];
        })
        ->values();

    return response()->json([
        'count' => $solicitudes->count(),
        'data' => $solicitudes,
    ]);
}

//buscaa proveedoir para generador de entradas y salidas
public function buscarProveedor(Request $request)
{
    $q = trim($request->get('q', ''));

    // Solo buscamos si hay 3 o más caracteres
    if (strlen($q) < 3) {
        return response()->json([]);
    }

    $proveedores = Proveedor::query()
        ->where('activo', true)
        ->where(function($query) use ($q) {
            $query->where('nombre', 'like', "%{$q}%")
                  ->orWhere('rfc', 'like', "%{$q}%");
        })
        ->limit(10)
        ->get(['id', 'nombre', 'rfc']);

    return response()->json($proveedores);
}
//buscar producto para generador de entradas ys alidas
public function buscarProducto(Request $request)
{
    $q = trim($request->get('q', ''));
    $almacenId = $request->integer('almacen_id');

    // Solo buscamos si hay 3 o más caracteres
    if (strlen($q) < 3) {
        return response()->json([]);
    }

    $productos = Producto::query()
        ->where('activo', true)
        ->where(function($query) use ($q) {
            $query->where('nombre', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%");
        })
        ->limit(10)
        ->get(['id', 'nombre', 'descripcion', 'sku']);

    $stockPorProducto = collect();

    if ($almacenId > 0 && $productos->isNotEmpty()) {
        $stockPorProducto = \App\Models\InventarioStock::query()
            ->where('almacen_id', $almacenId)
            ->whereIn('producto_id', $productos->pluck('id'))
            ->get(['producto_id', 'stock_actual', 'costo_promedio'])
            ->keyBy('producto_id');
    }

    return response()->json($productos->map(function ($producto) use ($stockPorProducto) {
        $stock = $stockPorProducto->get($producto->id);

        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'sku' => $producto->sku,
            'stock_actual' => $stock ? (float) $stock->stock_actual : null,
            'costo_promedio' => $stock ? (float) $stock->costo_promedio : null,
        ];
    })->values());
}

public function edit(InventarioDocumento $doc)
{
    return view('inventario.documentos.edit', compact('doc'));
}

public function editRelacion(InventarioDocumento $doc)
{
    if ($doc->tipo !== 'entrada' || $doc->estado !== 'aplicado') {
        return redirect()
            ->route('inventario.documentos.show', $doc)
            ->with('error', 'Solo se puede corregir la relación de una entrada aplicada.');
    }

    $doc->load(['mantenimientoAbastecimiento.mantenimiento', 'detalles.ordenCompraDetalle']);
    $ordenCompraIds = $doc->detalles
        ->pluck('ordenCompraDetalle.orden_compra_id')
        ->filter()
        ->unique()
        ->values();

    $destinoIds = OrdenCompra::query()
        ->whereIn('id', $ordenCompraIds)
        ->whereNotNull('mantenimiento_abastecimiento_id')
        ->pluck('mantenimiento_abastecimiento_id')
        ->filter()
        ->unique()
        ->push($doc->mantenimiento_abastecimiento_id)
        ->filter()
        ->unique()
        ->values();

    if ($destinoIds->isEmpty()) {
        return redirect()
            ->route('inventario.documentos.show', $doc)
            ->with('error', 'La entrada no tiene una relación de abastecimiento válida para corregir.');
    }

    $abastecimientos = MantenimientoAbastecimiento::query()
        ->with('mantenimiento.maquina')
        ->whereIn('id', $destinoIds)
        ->orderBy('id')
        ->get();

    return view('inventario.documentos.edit-relacion', compact('doc', 'abastecimientos'));
}

public function updateRelacion(Request $request, InventarioDocumento $doc)
{
    if ($doc->tipo !== 'entrada' || $doc->estado !== 'aplicado') {
        return redirect()
            ->route('inventario.documentos.show', $doc)
            ->with('error', 'Solo se puede corregir la relación de una entrada aplicada.');
    }

    $data = $request->validate([
        'mantenimiento_abastecimiento_id' => ['required', 'integer', 'exists:mantenimiento_abastecimientos,id'],
    ]);

    $doc->load(['mantenimientoAbastecimiento', 'detalles.ordenCompraDetalle.orden']);
    $origen = $doc->mantenimientoAbastecimiento;
    $destino = MantenimientoAbastecimiento::findOrFail($data['mantenimiento_abastecimiento_id']);

    $destinoEsperado = $doc->detalles
        ->map(fn ($detalle) => $detalle->ordenCompraDetalle?->orden?->mantenimiento_abastecimiento_id)
        ->filter()
        ->unique()
        ->values();

    if ($destinoEsperado->count() !== 1 || (int) $destinoEsperado->first() !== (int) $destino->id) {
        return back()
            ->withInput()
            ->withErrors(['mantenimiento_abastecimiento_id' => 'La nueva relación debe coincidir con el abastecimiento de las órdenes de compra de esta entrada.']);
    }

    if ((int) $destino->id === (int) $origen->id) {
        return back()
            ->withInput()
            ->withErrors(['mantenimiento_abastecimiento_id' => 'Selecciona una relación distinta a la actual.']);
    }

    DB::transaction(function () use ($doc, $destino) {
        $documento = InventarioDocumento::query()->lockForUpdate()->findOrFail($doc->id);
        $documento->mantenimiento_abastecimiento_id = $destino->id;
        $documento->save();
    });

    return redirect()
        ->route('inventario.documentos.show', $doc)
        ->with('status', "Relación corregida a {$destino->folio}. No se modificaron existencias ni cantidades.");
}

public function update(Request $request, InventarioDocumento $doc)
{
    if ($doc->estado !== 'borrador') {
        return redirect()->route('inventario.documentos.show', $doc)
            ->with('error', 'Solo se pueden editar documentos en borrador.');
    }

    $data = $request->validate([
        'detalles' => ['required', 'array'],
        'detalles.*.cantidad' => ['nullable', 'numeric', 'gt:0'],
    ]);

    foreach ($doc->detalles()->with('ordenCompraDetalle')->get() as $detalle) {
        $cantidadNueva = $data['detalles'][$detalle->id]['cantidad'] ?? null;
        $ocDetalle = $detalle->ordenCompraDetalle;

        if ($cantidadNueva === null || !$ocDetalle) {
            continue;
        }

        $pendiente = max(
            0,
            (float) $ocDetalle->cantidad - (float) $ocDetalle->cantidad_recibida
        );

        if ($pendiente <= 0) {
            continue;
        }

        if ((float) $cantidadNueva > $pendiente + 0.0005) {
            return redirect()->route('inventario.documentos.show', $doc)
                ->with('error', "La cantidad de {$detalle->descripcion} excede el saldo pendiente de la OC.");
        }

        $detalle->update(['cantidad' => $cantidadNueva]);
    }

    return redirect()->route('inventario.documentos.show', $doc)
        ->with('status', 'Documento actualizado correctamente.');
}
    public function buscarOrdenesCompra(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = OrdenCompra::query()
            ->with('proveedor')
            ->whereIn('estado', ['AUTORIZADA', 'APROBADA'])
            ->whereIn('estado_recepcion', ['pendiente', 'parcial'])
            ->whereHas('detalles', fn ($detalle) => $detalle->whereColumn('cantidad_recibida', '<', 'cantidad'))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('folio', 'like', "%{$q}%")
                      ->orWhere('id', 'like', "%{$q}%")
                      ->orWhereHas('proveedor', function ($prov) use ($q) {
                          $prov->where('nombre', 'like', "%{$q}%")
                               ->orWhere('rfc', 'like', "%{$q}%");
                      });
                });
            })
            ->orderByDesc('id');

        $ordenesCompra = $query->limit(10)->get([
            'id',
            'folio',
            'proveedor_id',
            'estado',
            'estado_recepcion',
            'total',
        ]);

        return response()->json($ordenesCompra->map(function (OrdenCompra $oc) {
            return [
                'id' => $oc->id,
                'folio' => $oc->folio ?: 'OC #' . $oc->id,
                'estado' => $oc->estado,
                'estado_recepcion' => $oc->estado_recepcion,
                'proveedor' => $oc->proveedor?->nombre ?? 'Sin proveedor',
                'total' => (float) ($oc->total ?? 0),
            ];
        })->values());
    }

    public function buscarGastosCajaChica(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $q = preg_replace('/^RCC-G-/i', '', $q);
        $qId = ltrim($q, '0');
        $qId = $qId === '' ? '0' : $qId;
        $almacenId = $request->integer('almacen_id');

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $gastos = ReposicionCajaChicaGasto::query()
            ->with(['proveedor:id,nombre,rfc', 'categoria:id,nombre', 'subcategoria:id,nombre', 'almacen:id,nombre'])
            ->where('destino', 'almacen')
            ->whereIn('estado_autorizacion', ['autorizado', 'autorizado_parcial'])
            ->when($almacenId > 0, fn ($query) => $query->where('almacen_id', $almacenId))
            ->where(function ($query) use ($q, $qId) {
                $query->where('id', 'like', "%{$qId}%")
                    ->orWhere('concepto', 'like', "%{$q}%")
                    ->orWhere('proveedor_nombre', 'like', "%{$q}%")
                    ->orWhere('proveedor_rfc', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json($gastos->map(function (ReposicionCajaChicaGasto $gasto) {
            return [
                'id' => $gasto->id,
                'folio' => 'RCC-G-' . str_pad((string) $gasto->id, 5, '0', STR_PAD_LEFT),
                'fecha' => optional($gasto->fecha_gasto)->format('Y-m-d'),
                'proveedor' => $gasto->proveedor_nombre ?: ($gasto->proveedor?->nombre ?? 'Sin proveedor'),
                'rfc' => $gasto->proveedor_rfc ?: ($gasto->proveedor?->rfc ?? ''),
                'concepto' => $gasto->concepto,
                'categoria' => $gasto->subcategoria?->nombre ?? $gasto->categoria?->nombre ?? 'Sin categoría',
                'importe_registrado' => (float) $gasto->importe_registrado,
                'importe_autorizado' => (float) ($gasto->importe_autorizado ?? 0),
                'estado' => $gasto->estado_autorizacion,
                'almacen' => $gasto->almacen?->nombre ?? 'Sin almacén',
            ];
        })->values());
    }

    public function detalleOrdenCompra(OrdenCompra $ordenCompra)
    {
        abort_unless(
            in_array($ordenCompra->estado, ['AUTORIZADA', 'APROBADA'], true)
                && in_array($ordenCompra->estado_recepcion, ['pendiente', 'parcial'], true),
            404
        );

        $ordenCompra->load(['proveedor', 'detalles.producto']);
        $detallesPendientes = $ordenCompra->detalles->filter(
            fn ($detalle) => (float) ($detalle->cantidad_pendiente ?? 0) > 0
        );

        abort_if($detallesPendientes->isEmpty(), 404);

        return response()->json([
            'id' => $ordenCompra->id,
            'folio' => $ordenCompra->folio ?: 'OC #' . $ordenCompra->id,
            'estado' => $ordenCompra->estado,
            'proveedor' => $ordenCompra->proveedor?->nombre ?? 'Sin proveedor',
            'detalles' => $detallesPendientes->map(function ($detalle) use ($ordenCompra) {
                $cantidadOrdenada = (float) ($detalle->cantidad ?? 0);
                $cantidadPendiente = (float) ($detalle->cantidad_pendiente ?? $cantidadOrdenada);

                return [
                    'orden_compra_id' => $ordenCompra->id,
                    'orden_compra_detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'requiere_vinculo_producto' => empty($detalle->producto_id),
                    'nombre' => $detalle->producto?->nombre ?? ($detalle->descripcion ?? 'Sin producto'),
                    'unidad' => $detalle->unidad ?? '',
                    'cantidad' => $cantidadPendiente,
                    'cantidad_ordenada' => $cantidadOrdenada,
                    'cantidad_pendiente' => $cantidadPendiente,
                    'costo_unitario' => (float) ($detalle->precio_unitario ?? 0),
                    'notas' => $detalle->notas ?? '',
                ];
            })->values(),
        ]);
    }

    public function store(Request $request, InventarioDocumentoService $service)
    {
        // El flujo general de productos corresponde a Giralda. No confiar en el ID del formulario.
        if (in_array($request->input('tipo'), ['entrada', 'salida'], true)) {
            $request->merge(['almacen_id' => $this->almacenGiralda()->id]);
        }
        $cantidadRule = $request->input('tipo') === 'salida'
            ? ['required', 'integer', 'gte:0']
            : ['required', 'numeric', 'gt:0'];

        $data = $request->validate([
            'tipo'        => ['required','in:inicial,entrada,salida,ajuste,resguardo,devolucion'],
            'almacen_id'  => ['required','integer'],
            'obra_id'     => ['nullable','integer','exists:obras,id'],
            'proveedor_id'=> ['nullable','integer'],
            'orden_compra_id' => ['nullable','integer'],
            'mantenimiento_abastecimiento_id' => ['nullable','integer','exists:mantenimiento_abastecimientos,id'],
            'fecha'       => ['nullable','date'],
            'motivo'      => ['nullable','string','max:120'],
            'notas'       => ['nullable','string'],
            'action'         => ['nullable','in:draft,apply'],

            'detalles'                         => ['required','array','min:1'],
            'detalles.*.producto_id'           => ['required','integer'],
            'detalles.*.orden_compra_id'       => ['nullable','integer','exists:ordenes_compra,id'],
            'detalles.*.orden_compra_detalle_id'=> ['nullable','integer','exists:orden_compra_detalles,id'],
            'detalles.*.cantidad'              => $cantidadRule,
            'detalles.*.costo_unitario'        => ['nullable','numeric','gte:0'],
            'detalles.*.notas'                 => ['nullable','string','max:150'],
            'detalles.*.equipo_activo'        => ['nullable','boolean'],
            'detalles.*.equipo_tipo'          => ['nullable','in:vehiculo,maquina'],
            'detalles.*.vehiculo_id'          => ['nullable','integer','exists:vehiculos,id'],
            'detalles.*.maquina_id'           => ['nullable','integer','exists:maquinas,id'],
        ]);

        if ($data['tipo'] === 'salida') {
            $data['detalles'] = collect($data['detalles'])
                ->filter(fn (array $detalle) => (int) $detalle['cantidad'] > 0)
                ->values()
                ->all();

            if (empty($data['detalles'])) {
                return back()
                    ->withInput()
                    ->withErrors(['detalles' => 'Captura al menos una cantidad positiva para aplicar la salida.']);
            }
        }

        if ($data['tipo'] === 'salida') {
            $abastecimiento = null;
            if (!empty($data['mantenimiento_abastecimiento_id'])) {
                $abastecimiento = MantenimientoAbastecimiento::query()
                    ->with('detalles')
                    ->whereKey($data['mantenimiento_abastecimiento_id'])
                    ->where(function ($query) {
                        $query->where(function ($directo) {
                            $directo->where('tipo', MantenimientoAbastecimiento::TIPO_ALMACEN)
                                ->whereNotIn('estado', ['surtida', 'cancelada']);
                        })->orWhere(function ($compra) {
                            $compra->where('tipo', MantenimientoAbastecimiento::TIPO_COMPRA)
                                ->whereIn('estado', ['recibida_parcial', 'material_en_almacen', 'parcial']);
                        });
                    })
                    ->first();

                if (!$abastecimiento) {
                    return back()->withInput()->withErrors(['mantenimiento_abastecimiento_id' => 'La solicitud de almacén ya no está disponible.']);
                }

                $pendientes = $abastecimiento->detalles->keyBy('producto_id')->map(
                    fn ($detalle) => max(0, (float) $detalle->cantidad_solicitada - (float) $detalle->cantidad_atendida)
                );

                $solicitadoEnSalida = [];
                foreach ($data['detalles'] as $index => $detalle) {
                    $productoId = (int) $detalle['producto_id'];
                    $pendiente = (float) ($pendientes->get($productoId, 0));
                    $solicitadoEnSalida[$productoId] = ($solicitadoEnSalida[$productoId] ?? 0) + (float) $detalle['cantidad'];
                    if ($pendiente <= 0 || $solicitadoEnSalida[$productoId] > $pendiente + 0.0005) {
                        return back()->withInput()->withErrors([
                            "detalles.{$index}.cantidad" => 'La cantidad excede el pendiente de la solicitud de almacén.',
                        ]);
                    }
                }
            }

            foreach ($data['detalles'] as $index => $detalle) {
                $equipoActivo = filter_var($detalle['equipo_activo'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $tipoEquipo = $detalle['equipo_tipo'] ?? null;
                $vehiculoId = $detalle['vehiculo_id'] ?? null;
                $maquinaId = $detalle['maquina_id'] ?? null;

                if ($vehiculoId && $maquinaId) {
                    return back()->withInput()->withErrors([
                        "detalles.{$index}.equipo" => 'Una partida no puede tener vehículo y máquina al mismo tiempo.',
                    ]);
                }

                if (!$equipoActivo && ($tipoEquipo || $vehiculoId || $maquinaId)) {
                    return back()->withInput()->withErrors([
                        "detalles.{$index}.equipo" => 'Activa la identificación de equipo antes de seleccionar un vehículo o máquina.',
                    ]);
                }

                if ($equipoActivo && (
                    !$tipoEquipo ||
                    ($tipoEquipo === 'vehiculo' && !$vehiculoId) ||
                    ($tipoEquipo === 'maquina' && !$maquinaId)
                )) {
                    return back()->withInput()->withErrors([
                        "detalles.{$index}.equipo" => 'Selecciona el tipo y el equipo destino de la partida.',
                    ]);
                }
            }
        }

        if ($data['tipo'] === 'salida' && !empty($data['obra_id'])) {
            $obraValida = Obra::query()
                ->whereKey($data['obra_id'])
                ->where('estatus_nuevo', Obra::ESTATUS_EJECUCION)
                ->exists();

            if (!$obraValida) {
                return back()->withInput()->withErrors([
                    'obra_id' => 'La obra destino debe estar en ejecución.',
                ]);
            }
        }

        // La salida puede ser para almacén o mantenimiento sin obra.
        if (in_array($data['tipo'], ['resguardo','devolucion'], true) && empty($data['obra_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'obra_id es obligatorio para resguardos y devoluciones.'
            ], 422);
        }

        $doc = InventarioDocumento::create([
            'tipo'           => $data['tipo'],
            'almacen_id'     => $data['almacen_id'],
            'obra_id'        => $data['obra_id'] ?? null,
            'proveedor_id'   => $data['proveedor_id'] ?? null,
            'orden_compra_id'=> $data['orden_compra_id'] ?? null,
            'mantenimiento_abastecimiento_id' => $data['mantenimiento_abastecimiento_id'] ?? null,
            'estado'         => 'borrador',
            'fecha'          => $data['fecha'] ?? now(),
            'motivo'         => $data['motivo'] ?? null,
            'notas'          => $data['notas'] ?? null,
            'creado_por'     => auth()->id() ?? ($data['creado_por'] ?? null),
            'residente_id'   => null, // luego lo llenamos derivándolo de la obra
        ]);

        if (in_array($doc->tipo, ['entrada', 'salida'], true) && empty($doc->folio)) {
            $prefijo = $doc->tipo === 'entrada' ? 'ENT-' : 'SAL-';

            $doc->update([
                'folio' => $prefijo . str_pad((string) $doc->id, 6, '0', STR_PAD_LEFT),
            ]);
        }

        foreach ($data['detalles'] as $d) {
            $equipoActivo = $data['tipo'] === 'salida'
                && filter_var($d['equipo_activo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $equipoTipo = $equipoActivo ? ($d['equipo_tipo'] ?? null) : null;
            $vehiculoId = $equipoTipo === 'vehiculo' ? ($d['vehiculo_id'] ?? null) : null;
            $maquinaId = $equipoTipo === 'maquina' ? ($d['maquina_id'] ?? null) : null;
            $equipoNombreSnapshot = null;
            $equipoIdentificadorSnapshot = null;

            if ($equipoTipo === 'vehiculo') {
                $vehiculo = Vehiculo::find($vehiculoId);

                if (!$vehiculo) {
                    return back()->withInput()->withErrors([
                        'detalles' => 'El vehículo seleccionado ya no está disponible.',
                    ]);
                }

                $equipoNombreSnapshot = trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? ''))
                    ?: 'Vehículo #' . $vehiculo->id;
                $equipoIdentificadorSnapshot = $vehiculo->placas ?: 'ID ' . $vehiculo->id;
            }

            if ($equipoTipo === 'maquina') {
                $maquina = Maquina::find($maquinaId);

                if (!$maquina) {
                    return back()->withInput()->withErrors([
                        'detalles' => 'La máquina seleccionada ya no está disponible.',
                    ]);
                }

                $equipoNombreSnapshot = $maquina->nombre
                    ?: trim(($maquina->marca ?? '') . ' ' . ($maquina->modelo ?? ''))
                    ?: 'Máquina #' . $maquina->id;
                $equipoIdentificadorSnapshot = $maquina->codigo ?: 'ID ' . $maquina->id;
            }

            InventarioDocumentoDetalle::create([
                'documento_id'              => $doc->id,
                'producto_id'               => $d['producto_id'],
                'vehiculo_id'               => $vehiculoId,
                'maquina_id'                => $maquinaId,
                'equipo_tipo'               => $equipoTipo,
                'equipo_nombre_snapshot'   => $equipoNombreSnapshot,
                'equipo_identificador_snapshot' => $equipoIdentificadorSnapshot,
                'orden_compra_id'           => $d['orden_compra_id'] ?? null,
                'orden_compra_detalle_id'   => $d['orden_compra_detalle_id'] ?? null,
                'cantidad'                  => $d['cantidad'],
                'costo_unitario'            => $d['costo_unitario'] ?? null,
                'notas'                     => $d['notas'] ?? null,
            ]);
        }

        if (($data['action'] ?? 'draft') === 'apply') {
            try {
                $service->aplicar($doc);
            } catch (\Throwable $e) {
                return redirect()
                    ->route('inventario.documentos.show', $doc)
                    ->withInput()
                    ->with('error', $e->getMessage());
            }

            return redirect()
                ->route('inventario.documentos.index', ['tipo' => $data['tipo']])
                ->with('status', "Documento #{$doc->id} guardado y aplicado correctamente.");
        }
       // Si la petición espera JSON (AJAX)
if (request()->expectsJson()) {
    return response()->json([
        'ok'   => true,
        'data' => $doc->load('detalles')
    ]);
}

// Web normal → redirigir
return redirect()
    ->route('inventario.documentos.index', ['tipo' => $data['tipo']])
    ->with('status', "Documento #{$doc->id} guardado correctamente.");

    }

 public function show(InventarioDocumento $doc)
{
    $doc->load([
        'almacen',
        'detalles.producto',
        'detalles.ordenCompraDetalle',
        'mantenimientoAbastecimiento.mantenimiento.maquina',
    ]);

    return view('inventario.documentos.show', compact('doc'));
}


    // public function aplicar(InventarioDocumento $doc)
    // {
    //     // Opcional: Gate/Policy aquí (ej. permisos inventario.aplicar)
    //     InventarioDocumentoService::aplicar($doc);

    //     return redirect()
    //         ->route('inventario.documentos.index')
    //         ->with('status', "Documento #{$doc->id} aplicado.");
    // }
    public function aplicar(InventarioDocumento $doc, InventarioDocumentoService $service)
    {
        try {
            $service->aplicar($doc);
        } catch (\Throwable $e) {
            return redirect()
                ->route('inventario.documentos.show', $doc)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventario.documentos.index', ['tipo' => $doc->tipo])
            ->with('status', "Documento #{$doc->id} aplicado.");
    }

    public function destroy(InventarioDocumento $doc)
    {
        if ($doc->tipo !== 'entrada') {
            return redirect()->route('inventario.documentos.index')
                ->with('error', 'Solo se pueden borrar documentos de entrada.');
        }

        if ($doc->estado !== 'borrador' || $doc->movimientos()->exists()) {
            return redirect()->route('inventario.documentos.index')
                ->with('error', 'No se puede borrar una entrada que ya tiene movimientos o fue aplicada. Usa Cancelar para generar la reversa.');
        }

        DB::transaction(function () use ($doc) {
            $doc->detalles()->delete();
            $doc->delete();
        });

        return redirect()->route('inventario.documentos.index')
            ->with('status', "Documento #{$doc->id} eliminado correctamente.");
    }

     
    private function almacenGiralda(): Almacen
    {
        return Almacen::query()->where('activo', true)
            ->where(function ($query) {
                $query->where('nombre', 'like', '%Giralda%')
                    ->orWhere('codigo', 'like', '%GIRALDA%')
                    ->orWhere('codigo', 'like', 'GL%');
            })
            ->orderBy('id')->firstOrFail();
    }

    public function cancelar(
        InventarioDocumento $doc,
        InventarioDocumentoService $service
    ) {
        $service->cancelar($doc);

        return redirect()
            ->route('inventario.documentos.index')
            ->with('status', "Documento #{$doc->id} cancelado (reversado).");
    }
}
