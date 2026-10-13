<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Area;
use App\Models\DocumentoFirmante;
use App\Models\Empleado;
use App\Models\EmpresaRequisicionTipo;
use App\Models\InventarioStock;
use App\Models\Maquina;
use App\Models\Obra;
use App\Models\ObraRequisicion;
use App\Models\ObraRequisicionDetalle;
use App\Models\ObraRequisicionFolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObraRequisicionController extends Controller
{
    public function index(Request $request, Obra $obra)
    {
        $requisiciones = $this->requisicionesDeObra($obra);
        $stats = $this->statsRequisiciones($requisiciones);

        return view('obras.requisiciones.index', compact('obra', 'requisiciones', 'stats'));
    }

    public function create(Request $request, Obra $obra)
    {
        $tiposRequisicion = EmpresaRequisicionTipo::query()
            ->activos()
            ->ordenados()
            ->get();

        return view('obras.requisiciones.create', compact('obra', 'tiposRequisicion'));
    }

    public function catalogo(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'tipo_id' => ['nullable', 'integer', 'exists:empresa_requisicion_tipos,id'],
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($data['tipo_id'])) {
            return response()->json([
                'tipo' => null,
                'items' => [],
            ]);
        }

        $tipo = EmpresaRequisicionTipo::query()
            ->with('almacen:id,nombre,codigo')
            ->whereKey($data['tipo_id'])
            ->where('activo', true)
            ->firstOrFail();

        $catalogoOptions = [
            'q' => trim((string) ($data['q'] ?? '')),
            'limit' => (int) ($data['limit'] ?? 100),
            'page' => (int) ($data['page'] ?? 1),
        ];

        $items = $this->itemsCatalogo($tipo, $obra, $catalogoOptions);

        return response()->json([
            'tipo' => [
                'id' => $tipo->id,
                'codigo' => $tipo->codigo,
                'numero_catalogo' => $tipo->numero_catalogo,
                'nombre' => $tipo->nombre,
                'descripcion' => $tipo->descripcion,
                'origen_catalogo' => $tipo->origen_catalogo,
                'item_type' => $tipo->item_type,
                'requiere_catalogo' => (bool) $tipo->requiere_catalogo,
                'permite_manual' => (bool) $tipo->permite_manual,
                'almacen' => $tipo->almacen ? [
                    'id' => $tipo->almacen->id,
                    'codigo' => $tipo->almacen->codigo,
                    'nombre' => $tipo->almacen->nombre,
                ] : null,
            ],
            'items' => $items->values(),
        ]);
    }

    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'tipo_requisicion_id' => ['required', 'integer', 'exists:empresa_requisicion_tipos,id'],
            'fecha_requerida' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['integer'],
            'selected_quantities' => ['nullable', 'array'],
            'selected_quantities.*' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
        ], [
            'tipo_requisicion_id.required' => 'Selecciona el tipo de requisicion.',
            'selected_items.array' => 'La seleccion de partidas no es valida.',
        ]);

        if (in_array((int) $obra->estatus_nuevo, [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA], true)) {
            return back()
                ->withErrors(['obra_id' => 'No se pueden crear requisiciones para obras terminadas o canceladas.'])
                ->withInput();
        }

        $tipoSeleccionado = EmpresaRequisicionTipo::query()
            ->whereKey($data['tipo_requisicion_id'])
            ->where('activo', true)
            ->firstOrFail();

        $selectedIds = collect($data['selected_items'] ?? [])
            ->map(fn ($itemId) => (int) $itemId)
            ->filter()
            ->unique()
            ->values();

        $selectedQuantities = collect($data['selected_quantities'] ?? [])
            ->mapWithKeys(fn ($cantidad, $itemId) => [(int) $itemId => (float) $cantidad]);

        $catalogoItems = $this->itemsCatalogo($tipoSeleccionado, $obra)->keyBy('id');
        $detallesSeleccionados = collect();

        if ($tipoSeleccionado->requiere_catalogo) {
            $detallesSeleccionados = $selectedIds
                ->filter(fn ($itemId) => $catalogoItems->has($itemId))
                ->map(function ($itemId) use ($catalogoItems, $selectedQuantities) {
                    $item = $catalogoItems->get($itemId);
                    $item['cantidad_solicitada'] = max(0.001, (float) ($selectedQuantities->get($itemId) ?: 1));

                    return $item;
                })
                ->values();

            if ($selectedIds->count() !== $detallesSeleccionados->count()) {
                return back()
                    ->withErrors(['selected_items' => 'La seleccion contiene partidas que ya no estan disponibles en el catalogo.'])
                    ->withInput();
            }

            if ($detallesSeleccionados->isEmpty()) {
                return back()
                    ->withErrors(['selected_items' => 'Selecciona al menos una partida para la requisicion.'])
                    ->withInput();
            }
        }

        $requisicion = DB::transaction(function () use ($data, $obra, $detallesSeleccionados) {
            $tipo = EmpresaRequisicionTipo::query()
                ->whereKey($data['tipo_requisicion_id'])
                ->where('activo', true)
                ->lockForUpdate()
                ->firstOrFail();

            $anio = (int) now('America/Mexico_City')->format('Y');
            $anioCorto = now('America/Mexico_City')->format('y');

            $folioControl = ObraRequisicionFolio::query()
                ->where('obra_id', $obra->id)
                ->where('empresa_requisicion_tipo_id', $tipo->id)
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            if (! $folioControl) {
                $folioControl = ObraRequisicionFolio::create([
                    'obra_id' => $obra->id,
                    'empresa_requisicion_tipo_id' => $tipo->id,
                    'anio' => $anio,
                    'prefijo' => 'REQ',
                    'numero_catalogo' => $tipo->numero_catalogo,
                    'ultimo_consecutivo' => 0,
                ]);

                $folioControl = ObraRequisicionFolio::query()
                    ->whereKey($folioControl->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $consecutivo = ((int) $folioControl->ultimo_consecutivo) + 1;
            $folio = sprintf('%s-%s-%s-%04d', $folioControl->prefijo, $anioCorto, $tipo->numero_catalogo, $consecutivo);

            $folioControl->update([
                'numero_catalogo' => $tipo->numero_catalogo,
                'ultimo_consecutivo' => $consecutivo,
            ]);

            $requisicion = ObraRequisicion::create([
                'obra_id' => $obra->id,
                'empresa_requisicion_tipo_id' => $tipo->id,
                'obra_requisicion_folio_id' => $folioControl->id,
                'folio' => $folio,
                'anio' => $anio,
                'numero_catalogo' => $tipo->numero_catalogo,
                'consecutivo' => $consecutivo,
                'estado' => ObraRequisicion::ESTADO_PENDIENTE_REVISION,
                'fecha_solicitud' => now('America/Mexico_City')->toDateString(),
                'fecha_requerida' => $data['fecha_requerida'] ?? null,
                'solicitado_por' => auth()->id(),
                'solicitado_at' => now(),
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($detallesSeleccionados as $item) {
                $this->crearDetalleDesdeCatalogo($requisicion, $item, (float) ($item['cantidad_solicitada'] ?? 1));
            }

            return $requisicion;
        });

        $partidas = $requisicion->detalles()->count();
        $mensajePartidas = $partidas === 1 ? '1 partida' : "{$partidas} partidas";

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones'])
            ->with('success', "Requisicion {$requisicion->folio} creada correctamente con {$mensajePartidas}.");
    }

    public function show(Obra $obra, ObraRequisicion $requisicion)
    {
        $requisicion = $this->requisicionDeObra($obra, $requisicion);
        $editable = $requisicion->estado === ObraRequisicion::ESTADO_PENDIENTE_REVISION;
        $modo = $editable ? 'edit' : 'show';
        $catalogoItems = $this->catalogoDisponibleParaEditar($requisicion, $obra, $editable);

        return view('obras.requisiciones.show_edit', compact('obra', 'requisicion', 'modo', 'editable', 'catalogoItems'));
    }

    public function edit(Obra $obra, ObraRequisicion $requisicion)
    {
        $requisicion = $this->requisicionDeObra($obra, $requisicion);
        $modo = 'edit';
        $editable = $requisicion->estado === ObraRequisicion::ESTADO_PENDIENTE_REVISION;
        $catalogoItems = $this->catalogoDisponibleParaEditar($requisicion, $obra, $editable);

        return view('obras.requisiciones.show_edit', compact('obra', 'requisicion', 'modo', 'editable', 'catalogoItems'));
    }

    public function imprimir(Obra $obra, ObraRequisicion $requisicion)
    {
        $requisicion = $this->requisicionDeObra($obra, $requisicion);

        $firmasImpresas = DocumentoFirmante::query()
            ->with('user:id,name')
            ->where('documento', 'obra_requisicion')
            ->whereIn('campo', [
                DocumentoFirmante::CAMPO_ELABORO,
                DocumentoFirmante::CAMPO_AUTORIZO,
                DocumentoFirmante::CAMPO_VOBO,
                'recibe',
            ])
            ->where('activo', true)
            ->get()
            ->keyBy('campo');

        if (! view()->exists('obras.requisiciones.imprimir')) {
            return response('Vista de impresion de requisicion pendiente de construir.', 501);
        }

        return view('obras.requisiciones.imprimir', compact('obra', 'requisicion', 'firmasImpresas'));
    }
    public function update(Request $request, Obra $obra, ObraRequisicion $requisicion)
    {
        $requisicion = $this->requisicionDeObra($obra, $requisicion);

        if ($requisicion->estado !== ObraRequisicion::ESTADO_PENDIENTE_REVISION) {
            return redirect()
                ->route('obras.requisiciones.show', [$obra, $requisicion])
                ->with('error', 'Solo se pueden editar requisiciones pendientes de revision.');
        }

        $data = $request->validate([
            'fecha_requerida' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'detalles' => ['nullable', 'array'],
            'detalles.*.cantidad_solicitada' => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
            'detalles.*.observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'detalles.*.cantidad_solicitada.required' => 'La cantidad solicitada es obligatoria.',
            'detalles.*.cantidad_solicitada.min' => 'La cantidad solicitada debe ser mayor a cero.',
        ]);

        DB::transaction(function () use ($data, $requisicion) {
            $requisicion->update([
                'fecha_requerida' => $data['fecha_requerida'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            $detallesInput = collect($data['detalles'] ?? []);

            foreach ($requisicion->detalles as $detalle) {
                $input = $detallesInput->get((string) $detalle->id, $detallesInput->get($detalle->id));

                if (! $input) {
                    continue;
                }

                $detalle->update([
                    'cantidad_solicitada' => $input['cantidad_solicitada'],
                    'observaciones' => $input['observaciones'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('obras.requisiciones.show', [$obra, $requisicion])
            ->with('success', "Requisicion {$requisicion->folio} actualizada correctamente.");
    }
    public function storeDetalle(Request $request, Obra $obra, ObraRequisicion $requisicion)
    {
        $requisicion = $this->requisicionDeObra($obra, $requisicion);

        if ($requisicion->estado !== ObraRequisicion::ESTADO_PENDIENTE_REVISION) {
            return redirect()
                ->route('obras.requisiciones.show', [$obra, $requisicion])
                ->with('error', 'Solo se pueden agregar partidas a requisiciones pendientes de revision.');
        }

        if (! $requisicion->tipo || ! $requisicion->tipo->requiere_catalogo) {
            return redirect()
                ->route('obras.requisiciones.edit', [$obra, $requisicion])
                ->with('error', 'Este tipo de requisicion no tiene catalogo disponible para agregar partidas.');
        }

        $data = $request->validate([
            'selected_items' => ['required', 'array', 'min:1'],
            'selected_items.*' => ['integer'],
        ], [
            'selected_items.required' => 'Selecciona al menos una partida para agregar.',
            'selected_items.min' => 'Selecciona al menos una partida para agregar.',
        ]);

        $selectedIds = collect($data['selected_items'])
            ->map(fn ($itemId) => (int) $itemId)
            ->filter()
            ->unique()
            ->values();

        $selectedQuantities = collect($data['selected_quantities'] ?? [])
            ->mapWithKeys(fn ($cantidad, $itemId) => [(int) $itemId => (float) $cantidad]);

        $catalogoItems = $this->itemsCatalogo($requisicion->tipo, $obra)->keyBy('id');
        $itemsExistentes = $requisicion->detalles()
            ->where('item_type', $requisicion->tipo->item_type)
            ->pluck('item_id')
            ->map(fn ($itemId) => (int) $itemId);

        $detallesSeleccionados = $selectedIds
            ->reject(fn ($itemId) => $itemsExistentes->contains($itemId))
            ->filter(fn ($itemId) => $catalogoItems->has($itemId))
            ->map(function ($itemId) use ($catalogoItems, $selectedQuantities) {
                $item = $catalogoItems->get($itemId);
                $item['cantidad_solicitada'] = max(0.001, (float) ($selectedQuantities->get($itemId) ?: 1));

                return $item;
            })
            ->values();

        if ($selectedIds->contains(fn ($itemId) => ! $catalogoItems->has($itemId))) {
            return back()
                ->withErrors(['selected_items' => 'La seleccion contiene partidas que ya no estan disponibles en el catalogo.'])
                ->withInput();
        }

        if ($detallesSeleccionados->isEmpty()) {
            return back()
                ->withErrors(['selected_items' => 'Las partidas seleccionadas ya estan en la requisicion.'])
                ->withInput();
        }

        DB::transaction(function () use ($requisicion, $detallesSeleccionados) {
            foreach ($detallesSeleccionados as $item) {
                $this->crearDetalleDesdeCatalogo($requisicion, $item, (float) ($item['cantidad_solicitada'] ?? 1));
            }
        });

        $cantidad = $detallesSeleccionados->count();
        $mensaje = $cantidad === 1 ? '1 partida agregada correctamente.' : "{$cantidad} partidas agregadas correctamente.";

        return redirect()
            ->route('obras.requisiciones.edit', [$obra, $requisicion])
            ->with('success', $mensaje);
    }
    public function destroyDetalle(Obra $obra, ObraRequisicion $requisicion, ObraRequisicionDetalle $detalle)
    {
        $requisicion = $this->requisicionDeObra($obra, $requisicion);
        abort_unless((int) $detalle->obra_requisicion_id === (int) $requisicion->id, 404);

        if ($requisicion->estado !== ObraRequisicion::ESTADO_PENDIENTE_REVISION) {
            return redirect()
                ->route('obras.requisiciones.show', [$obra, $requisicion])
                ->with('error', 'Solo se pueden eliminar partidas de requisiciones pendientes de revision.');
        }

        $totalDetalles = $requisicion->detalles()->count();

        if ($totalDetalles <= 1) {
            $folio = $requisicion->folio;
            $requisicion->delete();

            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones'])
                ->with('success', "Se elimino la ultima partida y tambien la requisicion {$folio}.");
        }

        $detalle->delete();

        return redirect()
            ->route('obras.requisiciones.edit', [$obra, $requisicion])
            ->with('success', 'Partida eliminada correctamente.');
    }
    private function crearDetalleDesdeCatalogo(ObraRequisicion $requisicion, array $item, ?float $cantidad = null): void
    {
        $cantidadSolicitada = max(0.001, (float) ($cantidad ?? $item['cantidad_solicitada'] ?? 1));

        $requisicion->detalles()->create([
            'item_type' => $item['item_type'],
            'item_id' => $item['id'],
            'descripcion' => $item['label'],
            'descripcion_snapshot' => $item['descripcion_snapshot'],
            'unidad' => $item['unidad'] ?? $this->unidadCatalogo($item['item_type'] ?? null),
            'cantidad_solicitada' => $cantidadSolicitada,
            'cantidad_surtida' => 0,
            'estado' => 'pendiente',
        ]);
    }

    private function catalogoDisponibleParaEditar(ObraRequisicion $requisicion, Obra $obra, bool $editable)
    {
        if (! $editable || ! $requisicion->tipo || ! $requisicion->tipo->requiere_catalogo) {
            return collect();
        }

        $itemsExistentes = $requisicion->detalles
            ->where('item_type', $requisicion->tipo->item_type)
            ->pluck('item_id')
            ->map(fn ($itemId) => (int) $itemId);

        return $this->itemsCatalogo($requisicion->tipo, $obra)
            ->reject(fn ($item) => $itemsExistentes->contains((int) $item['id']))
            ->values();
    }
    private function itemsCatalogo(EmpresaRequisicionTipo $tipo, Obra $obra, array $options = [])
    {
        if ($this->esCatalogoEmpleadosGiralda($tipo)) {
            return $this->catalogoEmpleadosGiralda($obra);
        }

        if ($this->esCatalogoMaquinaria($tipo)) {
            return $this->catalogoMaquinaria($obra);
        }

        if ($this->esCatalogoMaterialesGiralda($tipo)) {
            return $this->catalogoMaterialesGiralda($tipo, $options);
        }

        return collect();
    }

    private function esCatalogoEmpleadosGiralda(EmpresaRequisicionTipo $tipo): bool
    {
        return $tipo->origen_catalogo === 'giralda' && $tipo->item_type === 'empleado';
    }

    private function esCatalogoMaquinaria(EmpresaRequisicionTipo $tipo): bool
    {
        return $tipo->origen_catalogo === 'maquinaria' && $tipo->item_type === 'maquina';
    }

    private function esCatalogoMaterialesGiralda(EmpresaRequisicionTipo $tipo): bool
    {
        return $tipo->origen_catalogo === 'giralda' && $tipo->item_type === 'producto';
    }

    private function unidadCatalogo(?string $itemType): ?string
    {
        return match ($itemType) {
            'empleado' => 'persona',
            'maquina' => 'equipo',
            default => null,
        };
    }

    private function catalogoEmpleadosGiralda(Obra $obra)
    {
        $areaGiralda = $this->areaGiralda();

        if (! $areaGiralda) {
            return collect();
        }

        return Empleado::query()
            ->with(['areaRef', 'asignacionActiva.obra'])
            ->where('Area', $areaGiralda->id)
            ->where('Estatus', 1)
            ->orderBy('Apellidos')
            ->orderBy('Nombre')
            ->get([
                'id_Empleado',
                'Nombre',
                'Apellidos',
                'Puesto',
                'puesto_base',
                'Area',
                'Estatus',
            ])
            ->map(function (Empleado $empleado) use ($obra) {
                $asignacion = $empleado->asignacionActiva;
                $obraAsignada = $asignacion?->obra;
                $asignadoEstaObra = $obraAsignada && (int) $obraAsignada->id === (int) $obra->id;
                $asignadoOtraObra = $obraAsignada && ! $asignadoEstaObra;

                $badge = 'Disponible';
                $badgeKey = 'disponible';

                if ($asignadoEstaObra) {
                    $badge = 'Asignado a esta obra';
                    $badgeKey = 'asignado_esta_obra';
                } elseif ($asignadoOtraObra) {
                    $badge = 'Asignado a otra obra';
                    $badgeKey = 'asignado_otra_obra';
                }

                $nombre = trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? ''));
                $puesto = $empleado->Puesto ?: $empleado->puesto_base;

                return [
                    'id' => $empleado->id_Empleado,
                    'item_type' => 'empleado',
                    'label' => $nombre,
                    'subtitle' => $puesto,
                    'descripcion_snapshot' => trim($nombre . ' - ' . ($puesto ?: 'Sin puesto')),
                    'badge' => $badge,
                    'badge_key' => $badgeKey,
                    'asignacion' => $obraAsignada ? [
                        'obra_id' => $obraAsignada->id,
                        'obra_nombre' => $obraAsignada->nombre,
                        'obra_clave' => $obraAsignada->clave_obra,
                    ] : null,
                ];
            });
    }

    private function catalogoMaquinaria(Obra $obra)
    {
        return Maquina::query()
            ->with(['asignacionActiva.obra:id,nombre,clave_obra'])
            ->orderBy('codigo')
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'tipo',
                'marca',
                'modelo',
                'estado',
                'ubicacion',
            ])
            ->map(function (Maquina $maquina) use ($obra) {
                $asignacion = $maquina->asignacionActiva;
                $obraAsignada = $asignacion?->obra;
                $asignadaEstaObra = $obraAsignada && (int) $obraAsignada->id === (int) $obra->id;
                $asignadaOtraObra = $obraAsignada && ! $asignadaEstaObra;

                $estadoLabel = match ((string) $maquina->estado) {
                    Maquina::ESTADO_OPERATIVA => 'Operativa',
                    Maquina::ESTADO_FUERA_SERVICIO => 'Fuera de servicio',
                    Maquina::ESTADO_EN_REPARACION => 'En reparacion',
                    Maquina::ESTADO_BAJA_DEFINITIVA => 'Baja definitiva',
                    default => $maquina->estado ? ucfirst(str_replace('_', ' ', (string) $maquina->estado)) : 'Sin estado',
                };

                $badge = 'Disponible';
                $badgeKey = 'disponible';

                if ($asignadaEstaObra) {
                    $badge = 'Asignada a esta obra';
                    $badgeKey = 'asignado_esta_obra';
                } elseif ($asignadaOtraObra) {
                    $badge = 'Asignada a otra obra';
                    $badgeKey = 'asignado_otra_obra';
                }

                $nombre = trim(($maquina->codigo ? $maquina->codigo . ' - ' : '') . ($maquina->nombre ?? '')) ?: 'Maquina';
                $tipo = $maquina->tipo ?: 'Sin tipo';
                $marcaModelo = trim(($maquina->marca ?? '') . ' ' . ($maquina->modelo ?? ''));
                $snapshotPartes = array_filter([
                    $nombre,
                    $tipo,
                    $marcaModelo ?: null,
                    $estadoLabel,
                    $obraAsignada ? 'Obra: ' . $obraAsignada->nombre : null,
                ]);

                return [
                    'id' => $maquina->id,
                    'item_type' => 'maquina',
                    'label' => $nombre,
                    'subtitle' => trim($tipo . ($marcaModelo ? ' - ' . $marcaModelo : '')),
                    'descripcion_snapshot' => implode(' | ', $snapshotPartes),
                    'badge' => $badge,
                    'badge_key' => $badgeKey,
                    'estado' => $maquina->estado,
                    'estado_label' => $estadoLabel,
                    'ubicacion' => $maquina->ubicacion,
                    'asignacion' => $obraAsignada ? [
                        'obra_id' => $obraAsignada->id,
                        'obra_nombre' => $obraAsignada->nombre,
                        'obra_clave' => $obraAsignada->clave_obra,
                    ] : null,
                ];
            });
    }
    private function catalogoMaterialesGiralda(EmpresaRequisicionTipo $tipo, array $options = [])
    {
        $almacen = $tipo->almacen ?: $this->almacenGiralda();

        if (! $almacen) {
            return collect();
        }

        $q = trim((string) ($options['q'] ?? ''));
        $limit = isset($options['limit']) ? max(1, min(100, (int) $options['limit'])) : null;
        $page = max(1, (int) ($options['page'] ?? 1));

        if (($options['remote'] ?? false) && $q === '') {
            return collect();
        }

        $query = InventarioStock::query()
            ->with(['producto:id,nombre,descripcion,sku,unidad,tipo_inventario,stock_minimo,punto_reorden,activo', 'almacen:id,nombre,codigo'])
            ->where('almacen_id', $almacen->id)
            ->whereHas('producto', function ($producto) use ($q) {
                $producto->where('activo', true);

                if ($q !== '') {
                    $producto->where(function ($query) use ($q) {
                        $query->where('nombre', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%")
                            ->orWhere('unidad', 'like', "%{$q}%")
                            ->orWhere('legacy_prod_id', 'like', "%{$q}%");
                    });
                }
            })
            ->orderBy(
                DB::raw('(select nombre from productos where productos.id = inventario_stock.producto_id)')
            );

        if ($limit !== null) {
            $query->forPage($page, $limit);
        }

        return $query
            ->get()
            ->map(function (InventarioStock $stock) use ($almacen) {
                $producto = $stock->producto;

                if (! $producto) {
                    return null;
                }

                $stockActual = (float) ($stock->stock_actual ?? 0);
                $stockReservado = (float) ($stock->stock_reservado ?? 0);
                $stockDisponible = max(0, $stockActual - $stockReservado);
                $stockMinimo = $producto->stock_minimo !== null ? (float) $producto->stock_minimo : null;

                $badge = 'Disponible: ' . number_format($stockDisponible, 3);
                $badgeKey = 'stock_disponible';

                if ($stockDisponible <= 0) {
                    $badge = 'Sin existencia';
                    $badgeKey = 'stock_cero';
                } elseif ($stockMinimo !== null && $stockDisponible <= $stockMinimo) {
                    $badge = 'Bajo minimo: ' . number_format($stockDisponible, 3);
                    $badgeKey = 'stock_bajo';
                }

                $sku = $producto->sku ?: $producto->codigo;
                $unidad = $producto->unidad ?: 'unidad';
                $subtitle = trim(($sku ?: 'Sin SKU') . ' / ' . $unidad);
                $snapshotPartes = array_filter([
                    $producto->nombre,
                    $sku ? 'SKU: ' . $sku : null,
                    'Unidad: ' . $unidad,
                    'Almacen: ' . $almacen->nombre,
                    'Existencia: ' . number_format($stockDisponible, 3),
                ]);

                return [
                    'id' => $producto->id,
                    'item_type' => 'producto',
                    'label' => $producto->nombre,
                    'subtitle' => $subtitle,
                    'descripcion_snapshot' => implode(' | ', $snapshotPartes),
                    'badge' => $badge,
                    'badge_key' => $badgeKey,
                    'sku' => $sku,
                    'unidad' => $unidad,
                    'stock_actual' => $stockActual,
                    'stock_reservado' => $stockReservado,
                    'stock_disponible' => $stockDisponible,
                    'stock_minimo' => $stockMinimo,
                    'almacen' => [
                        'id' => $almacen->id,
                        'codigo' => $almacen->codigo,
                        'nombre' => $almacen->nombre,
                    ],
                    'asignacion' => null,
                ];
            })
            ->filter()
            ->values();
    }

    private function almacenGiralda(): ?Almacen
    {
        return Almacen::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->where('codigo', 'like', '%GIRALDA%')
                    ->orWhere('nombre', 'like', '%Giralda%');
            })
            ->orderBy('id')
            ->first();
    }
    private function areaGiralda(): ?Area
    {
        return Area::query()
            ->where('codigo', 'GL')
            ->orWhere('nombre', 'like', '%Giralda%')
            ->first();
    }

    private function requisicionesDeObra(Obra $obra)
    {
        return ObraRequisicion::query()
            ->with(['tipo', 'solicitadoPor'])
            ->withCount('detalles')
            ->where('obra_id', $obra->id)
            ->latest('id')
            ->get();
    }

    private function requisicionDeObra(Obra $obra, ObraRequisicion $requisicion): ObraRequisicion
    {
        abort_unless((int) $requisicion->obra_id === (int) $obra->id, 404);

        return $requisicion->load(['tipo', 'solicitadoPor', 'detalles']);
    }
    private function statsRequisiciones($requisiciones): array
    {
        return [
            'total' => $requisiciones->count(),
            'pendientes' => $requisiciones->where('estado', ObraRequisicion::ESTADO_PENDIENTE_REVISION)->count(),
            'autorizadas' => $requisiciones->where('estado', ObraRequisicion::ESTADO_AUTORIZADA)->count(),
            'en_surtido' => $requisiciones->whereIn('estado', [
                ObraRequisicion::ESTADO_EN_SURTIDO,
                ObraRequisicion::ESTADO_SURTIDA_PARCIAL,
            ])->count(),
        ];
    }
}
















