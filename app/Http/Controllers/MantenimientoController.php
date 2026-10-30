<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\EmpresaServicioPreventivoMaterial;
use App\Models\Almacen;
use App\Models\InventarioStock;
use App\Models\InventarioDocumento;
use App\Models\Mantenimiento;
use App\Models\MantenimientoAbastecimiento;
use App\Models\Maquina;
use App\Models\OrdenCompra;
use App\Models\Vehiculo;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Mantenimiento\MantenimientoAbastecimientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoController extends Controller
{
    public function __construct(private \App\Services\Mantenimiento\MantenimientoCatalogoService $catalogos, private \App\Services\Mantenimiento\MantenimientoBorradorService $borradores)
    {
        $this->middleware('permission:maquinas.servicios.view.access')->only(['index', 'show']);
        $this->middleware('permission:maquinas.servicios.edit.access')->only(['create', 'store', 'edit', 'update', 'generarOrdenServicio', 'actualizarMaterialesOrden', 'solicitarAbastecimiento']);
    }

    /**
     * Listado
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with(['vehiculo', 'maquina', 'mecanico'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('mantenimiento.index', compact('mantenimientos'));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $request, MaquinaHorometroService $horometroService)
    {
        $vehiculos = Vehiculo::orderBy('marca')->orderBy('modelo')->get();
        $maquinas = Maquina::orderBy('nombre')->get();
        $maquinas->each(function (Maquina $maquina) use ($horometroService) {
            $maquina->horometro_actual_sugerido = $horometroService->horometroSugeridoParaAsignacion($maquina);
        });

        $vehiculoIdFromUrl = $request->query('vehiculo_id');
        $maquinaIdFromUrl = $request->query('maquina_id');
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();
        $serviciosPreventivosMaquinaria = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA);
        $serviciosPreventivosVehiculos = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_VEHICULO);
        $materialesPreventivosMaquina = $this->materialesPreventivosParaMaquina($maquinaIdFromUrl);

        return view('mantenimiento.create', compact(
            'vehiculos',
            'maquinas',
            'mecanicos',
            'vehiculoIdFromUrl',
            'maquinaIdFromUrl',
            'serviciosPreventivosMaquinaria',
            'serviciosPreventivosVehiculos',
            'materialesPreventivosMaquina'
        ));
    }

    /**
     * Registrar servicio realizado
     */
    public function store(Request $request)
    {
        $mantenimiento = $this->borradores->crear($request->all());

        return redirect()
            ->route('mantenimiento.mantenimientos.edit', $mantenimiento)
            ->with('success', 'Servicio guardado como borrador. Define el abastecimiento para continuar.');
    }
    /**
     * Mostrar detalle
     */
    public function show(Request $request, Mantenimiento $mantenimiento)
    {
        $returnTo = $this->safeReturnUrl($request->input('return_to'));

        if (auth()->user()?->can('maquinas.servicios.edit.access')) {
            return redirect()->route('mantenimiento.mantenimientos.edit', array_filter([
                'mantenimiento' => $mantenimiento,
                'return_to' => $returnTo,
            ]));
        }

        $mantenimiento->load(['vehiculo', 'maquina', 'obra', 'mecanico', 'detalles', 'fotos']);

        return view('mantenimiento.show', compact('mantenimiento', 'returnTo'));
    }

    /**
     * Formulario edición
     */
    public function edit(
        Request $request,
        Mantenimiento $mantenimiento,
        MantenimientoAbastecimientoService $abastecimientoService,
        MaquinaHorometroService $horometroService
    )
    {
        $vehiculos = Vehiculo::orderBy('marca')->get();
        $maquinas = Maquina::orderBy('nombre')->get();
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();
        $serviciosPreventivosMaquinaria = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA);
        $serviciosPreventivosVehiculos = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_VEHICULO);
        $materialesPreventivosMaquina = $this->materialesPreventivosParaMaquina($mantenimiento->maquina_id);
        $materialesOrden = $mantenimiento->materialesOrden()->get();
        $detalles = $mantenimiento->detalles()->get();
        $materialesAbastecimiento = $abastecimientoService->calcular($mantenimiento);
        $abastecimientos = $mantenimiento->abastecimientos()->with('detalles')->latest('id')->get();
        $ordenServicioDatos = $this->datosOrdenServicio($mantenimiento, $horometroService);

        $returnTo = $this->safeReturnUrl($request->input('return_to'));

        return view('mantenimiento.edit', compact(
            'mantenimiento',
            'vehiculos',
            'maquinas',
            'mecanicos',
            'serviciosPreventivosMaquinaria',
            'serviciosPreventivosVehiculos',
            'materialesPreventivosMaquina',
            'materialesOrden',
            'detalles',
            'materialesAbastecimiento',
            'abastecimientos',
            'ordenServicioDatos',
            'returnTo'
        ));
    }

    public function solicitarAbastecimiento(
        Request $request,
        Mantenimiento $mantenimiento,
        MantenimientoAbastecimientoService $abastecimientoService
    ) {
        if ($mantenimiento->estatus === 'borrador' || !$mantenimiento->orden_servicio_folio) {
            return back()->withErrors([
                'abastecimiento' => 'Primero debes generar la orden de servicio antes de solicitar material.',
            ]);
        }

        if ($mantenimiento->estatus === 'completado') {
            return back()->withErrors([
                'abastecimiento' => 'El servicio ya fue ejecutado y no admite nuevas solicitudes de material.',
            ]);
        }

        $data = $request->validate([
            'tipo' => 'required|in:almacen,compra',
        ]);

        try {
            $solicitud = $abastecimientoService->crearSolicitud(
                $mantenimiento,
                $data['tipo'],
                auth()->id()
            );
        } catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $exception) {
            return back()->withErrors(['abastecimiento' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('mantenimiento.mantenimientos.edit', $mantenimiento)
            ->with('success', "Solicitud {$solicitud->folio} generada correctamente.");
    }

    public function generarOrdenServicio(Mantenimiento $mantenimiento, \App\Services\Mantenimiento\MantenimientoOrdenService $ordenes)
    {
        try {
            $ordenes->generar($mantenimiento, (int) auth()->id());
        } catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Orden de servicio generada correctamente.');
    }

    public function actualizarMaterialesOrden(Request $request, Mantenimiento $mantenimiento, \App\Services\Mantenimiento\MantenimientoMaterialesService $materiales)
    {
        try {
            $materiales->actualizar($mantenimiento, $request->only(['incluidos', 'motivos']), null, true);
        } catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $exception) {
            return back()->withErrors(['materiales' => $exception->getMessage()]);
        }

        return back()->with('success', 'Materiales de la orden actualizados correctamente.');
    }

    public function imprimirAbastecimiento(MantenimientoAbastecimiento $abastecimiento)
    {
        $abastecimiento->load(['mantenimiento.maquina', 'mantenimiento.servicioPreventivoTipo', 'detalles']);

        $almacenGiralda = Almacen::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereRaw('LOWER(codigo) = ?', ['gl'])
                    ->orWhereRaw('LOWER(nombre) like ?', ['%giralda%']);
            })
            ->orderByRaw("CASE WHEN LOWER(codigo) = 'gl' THEN 0 ELSE 1 END")
            ->first();

        $stockPorProducto = $almacenGiralda
            ? InventarioStock::query()
                ->where('almacen_id', $almacenGiralda->id)
                ->whereIn('producto_id', $abastecimiento->detalles->pluck('producto_id')->filter()->unique())
                ->get()
                ->keyBy('producto_id')
            : collect();

        $existencias = $abastecimiento->detalles->mapWithKeys(function ($detalle) use ($stockPorProducto) {
            $stock = $stockPorProducto->get($detalle->producto_id);
            $disponible = max(0, (float) ($stock?->stock_actual ?? 0) - (float) ($stock?->stock_reservado ?? 0));

            return [$detalle->id => [
                'disponible' => $disponible,
                'minimo_compra' => max(0, (float) $detalle->cantidad_solicitada - $disponible),
            ]];
        });

        return view('mantenimiento.abastecimiento-print', compact('abastecimiento', 'existencias'));
    }

    public function imprimirOrdenServicio(
        Mantenimiento $mantenimiento,
        MaquinaHorometroService $horometroService
    ) {
        $mantenimiento->load(['maquina', 'vehiculo', 'obra', 'mecanico', 'servicioPreventivoTipo']);

        if (!$mantenimiento->orden_servicio_folio) {
            abort(404, 'La orden de servicio todavía no ha sido generada.');
        }

        $ordenServicioDatos = $this->datosOrdenServicio($mantenimiento, $horometroService);
        $materialesOrden = $mantenimiento->materialesOrden()->where('incluido', true)->get();

        return view('mantenimiento.orden-servicio-print', compact(
            'mantenimiento',
            'ordenServicioDatos',
            'materialesOrden'
        ));
    }

    public function imprimirReporte(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load([
            'vehiculo',
            'maquina',
            'obra',
            'mecanico',
            'servicioPreventivoTipo',
            'detalles',
            'abastecimientos.detalles',
            'abastecimientos.inventarioDocumentos.detalles',
        ]);

        $abastecimientoIds = $mantenimiento->abastecimientos->pluck('id');
        $ordenesCompra = OrdenCompra::query()
            ->whereIn('mantenimiento_abastecimiento_id', $abastecimientoIds)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get(['id', 'folio', 'estado', 'fecha']);

        $documentosInventario = InventarioDocumento::query()
            ->where(function ($query) use ($abastecimientoIds, $ordenesCompra) {
                $query->whereIn('mantenimiento_abastecimiento_id', $abastecimientoIds);

                if ($ordenesCompra->isNotEmpty()) {
                    $query->orWhereIn('orden_compra_id', $ordenesCompra->pluck('id'));
                    // Una entrada puede recibir varias OCs y conservar el vínculo solo en sus partidas.
                    $query->orWhereHas('detalles', fn ($detalle) => $detalle
                        ->whereIn('orden_compra_id', $ordenesCompra->pluck('id')));
                    $query->orWhereHas('detalles.ordenCompraDetalle', fn ($detalleCompra) => $detalleCompra
                        ->whereIn('orden_compra_id', $ordenesCompra->pluck('id')));
                }
            })
            ->whereIn('tipo', ['entrada', 'salida'])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get(['id', 'folio', 'tipo', 'estado', 'fecha', 'orden_compra_id', 'mantenimiento_abastecimiento_id']);

        $materiales = $mantenimiento->abastecimientos
            ->flatMap(fn ($abastecimiento) => $abastecimiento->detalles)
            ->groupBy(fn ($detalle) => $detalle->producto_id ?: 'snapshot:' . $detalle->producto_sku_snapshot . ':' . $detalle->producto_nombre_snapshot)
            ->map(function ($detalles) {
                $primero = $detalles->first();
                // Una necesidad puede tener varios abastecimientos (almacén,
                // compra o parcial), pero debe mostrarse una sola vez.
                $cantidadSolicitada = (float) $detalles->max('cantidad_requerida');
                $cantidadAtendida = min(
                    $cantidadSolicitada,
                    (float) $detalles->sum('cantidad_atendida')
                );

                return [
                    'nombre' => $primero->producto_nombre_snapshot ?: 'Material',
                    'sku' => $primero->producto_sku_snapshot,
                    'unidad' => $primero->unidad,
                    'solicitada' => $cantidadSolicitada,
                    'atendida' => $cantidadAtendida,
                ];
            })
            ->values();

        $costoInsumos = (float) $mantenimiento->abastecimientos
            ->flatMap(fn ($abastecimiento) => $abastecimiento->inventarioDocumentos)
            ->where('estado', 'aplicado')
            ->flatMap(fn ($documento) => $documento->detalles)
            ->sum(fn ($detalle) => (float) $detalle->cantidad * (float) $detalle->costo_unitario);

        $manoObraCalculada = $this->calcularManoDeObra($mantenimiento);

        return view('mantenimiento.reporte-print', compact(
            'mantenimiento',
            'materiales',
            'costoInsumos',
            'manoObraCalculada',
            'ordenesCompra',
            'documentosInventario'
        ));
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(
        Request $request,
        Mantenimiento $mantenimiento,
        MaquinaHorometroService $horometroService,
        \App\Services\Mantenimiento\MantenimientoEvidenciaService $evidencias
    )
    {
        if (in_array($mantenimiento->estatus, ['completado', 'cancelado'], true)) {
            return back()->withErrors(['estatus' => 'El servicio está cerrado y sus datos son de solo consulta.']);
        }
        $validated = $request->validate(array_merge($this->borradores->reglas(false), [
            'fecha_inicio' => 'nullable|required_if:estatus,completado|date',
            'fecha_fin' => 'nullable|required_if:estatus,completado|date|after:fecha_inicio',
            'fotos' => 'nullable|array|max:8',
            'fotos.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_descripciones' => 'nullable|array|max:8',
            'foto_descripciones.*' => 'nullable|string|max:200',
            'estatus' => 'required|in:borrador,orden_generada,pendiente,en_proceso,completado,cancelado',
            'abastecimiento_tipo' => 'nullable|in:surtido_directo,compra,parcial',
            'abastecimiento_estado' => 'nullable|in:pendiente,solicitud_almacen,solicitud_compra,parcial,listo',
        ]));

        if ($mantenimiento->estatus === 'borrador' && $validated['estatus'] === 'borrador'
            && empty($validated['abastecimiento_tipo']) && !$request->hasFile('fotos')) {
            try {
                $mantenimiento = $this->borradores->actualizar($mantenimiento, $validated);
            } catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $exception) {
                return back()->withErrors(['estatus' => $exception->getMessage()])->withInput();
            }

            return redirect()->route('mantenimiento.mantenimientos.edit', array_filter([
                'mantenimiento' => $mantenimiento->id,
                'return_to' => $this->safeReturnUrl($request->input('return_to')),
            ]))->with('success', 'Mantenimiento actualizado correctamente.');
        }
        $tieneAbastecimientos = $mantenimiento->abastecimientos()->exists();

        if ($tieneAbastecimientos) {
            $cambioEstructural = (int) ($validated['maquina_id'] ?? 0) !== (int) ($mantenimiento->maquina_id ?? 0)
                || (int) ($validated['vehiculo_id'] ?? 0) !== (int) ($mantenimiento->vehiculo_id ?? 0)
                || (int) ($validated['servicio_preventivo_tipo_id'] ?? 0) !== (int) ($mantenimiento->servicio_preventivo_tipo_id ?? 0)
                || ($validated['tipo'] ?? null) !== $mantenimiento->tipo;

            if ($cambioEstructural || ($validated['estatus'] ?? null) === 'borrador') {
                return back()->withErrors([
                    'estatus' => 'El activo, tipo de servicio y estado de la orden quedan bloqueados después de solicitar abastecimiento.',
                ])->withInput();
            }
        }

        if ($mantenimiento->estatus === 'completado' && ($validated['estatus'] ?? null) !== 'completado') {
            return back()->withErrors([
                'estatus' => 'Un servicio completado no puede reabrirse desde esta pantalla.',
            ])->withInput();
        }

        $this->borradores->validarCabecera($validated);

        $fotosNuevas = $request->file('fotos', []);
        if ($mantenimiento->fotos()->count() + count($fotosNuevas) > 8) {
            return back()
                ->withErrors(['fotos' => 'Un servicio puede tener como máximo 8 fotografías de evidencia.'])
                ->withInput();
        }

        if ($validated['estatus'] === 'completado'
            && !empty($validated['fecha_inicio'])
            && !empty($validated['fecha_fin'])
            && !\Carbon\Carbon::parse($validated['fecha_fin'])->isAfter(\Carbon\Carbon::parse($validated['fecha_inicio']))) {
            return back()
                ->withErrors(['fecha_fin' => 'La fecha y hora de fin deben ser posteriores al inicio de ejecución.'])
                ->withInput();
        }

        if (!empty($validated['abastecimiento_tipo']) && !in_array($validated['estatus'] ?? null, ['en_proceso', 'completado', 'cancelado'], true)) {
            $validated['estatus'] = 'pendiente';
            $validated['abastecimiento_estado'] = match ($validated['abastecimiento_tipo']) {
                Mantenimiento::ABASTECIMIENTO_SURTIDO_DIRECTO => 'solicitud_almacen',
                Mantenimiento::ABASTECIMIENTO_COMPRA => 'solicitud_compra',
                Mantenimiento::ABASTECIMIENTO_PARCIAL => 'parcial',
            };
        } else {
            $validated['abastecimiento_estado'] = 'pendiente';
        }

        $detalles = collect($validated['detalles'] ?? [])->map(function (array $detalle) {
            $cantidad = (float) $detalle['cantidad'];
            $costoUnitario = (float) ($detalle['costo_unitario'] ?? 0);

            return [
                'concepto' => $detalle['concepto'],
                'tipo' => $detalle['tipo'] ?? null,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => round($cantidad * $costoUnitario, 2),
            ];
        });
        unset($validated['detalles']);

        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = \Carbon\Carbon::parse($validated[$campo])->setTimezone(config('app.timezone'))->toDateTimeString();
            }
        }

        $persistir = function (Mantenimiento $mantenimiento) use ($validated, $detalles): void {
            $mantenimiento->update($validated);

            if ($mantenimiento->tipo === 'emergencia') {
                $mantenimiento->detalles()->delete();
                if ($detalles->isNotEmpty()) {
                    $mantenimiento->detalles()->createMany($detalles->all());
                }

                $mantenimiento->update([
                    'costo_mano_obra' => $detalles->where('tipo', 'mano_obra')->sum('costo_total'),
                    'costo_refacciones' => $detalles->whereIn('tipo', ['refaccion', 'consumible'])->sum('costo_total'),
                    'costo_total' => $detalles->sum('costo_total'),
                ]);
            }

            if ($mantenimiento->estatus === 'completado') {
                $this->actualizarManoDeObra($mantenimiento->fresh(['mecanico']));
            }
        };

        $guardar = function (Mantenimiento $actual) use ($validated, $persistir): void {
            app(\App\Services\Mantenimiento\MantenimientoEjecucionService::class)->guardarWeb($actual, $validated, $persistir);
        };

        if ($fotosNuevas !== []) {
            $evidencias->agregar($mantenimiento, [
                'fotos' => $fotosNuevas,
                'foto_descripciones' => $request->input('foto_descripciones', []),
            ], null, $guardar);
        } else {
            $guardar($mantenimiento);
        }

        $query = array_filter([
            'mantenimiento' => $mantenimiento->id,
            'return_to' => $this->safeReturnUrl($request->input('return_to')),
        ]);

        return redirect()->route('mantenimiento.mantenimientos.edit', $query)
            ->with('success', 'Mantenimiento actualizado correctamente.');
    }

    private function actualizarManoDeObra(Mantenimiento $mantenimiento): void
    {
        app(\App\Services\Mantenimiento\MantenimientoManoObraService::class)->actualizar($mantenimiento);
    }

    private function calcularManoDeObra(Mantenimiento $mantenimiento): ?array
    {
        return app(\App\Services\Mantenimiento\MantenimientoManoObraService::class)->calcular($mantenimiento);
    }

    private function safeReturnUrl(?string $returnTo): ?string
    {
        return is_string($returnTo)
            && str_starts_with($returnTo, '/')
            && !str_starts_with($returnTo, '//')
            ? $returnTo
            : null;
    }

    private function serviciosPreventivosPorAmbito(string $ambito)
    {
        return $this->catalogos->serviciosPreventivosPorAmbito($ambito);
    }

    private function materialesPreventivosParaMaquina(?int $maquinaId): array
    {
        return $this->catalogos->materialesPreventivosParaMaquina($maquinaId);
    }
    private function datosOrdenServicio(Mantenimiento $mantenimiento, MaquinaHorometroService $horometroService): array
    {
        $mantenimiento->loadMissing(['maquina', 'vehiculo', 'obra', 'servicioPreventivoTipo']);

        $esMaquina = (bool) $mantenimiento->maquina_id;
        $ultimoServicioQuery = Mantenimiento::query()
            ->where('id', '<>', $mantenimiento->id)
            ->where('estatus', 'completado')
            ->where('tipo', $mantenimiento->tipo);

        if ($esMaquina) {
            $ultimoServicioQuery->where('maquina_id', $mantenimiento->maquina_id);
        } else {
            $ultimoServicioQuery->where('vehiculo_id', $mantenimiento->vehiculo_id);
        }

        if ($mantenimiento->tipo === 'programado' && $mantenimiento->servicio_preventivo_tipo_id) {
            $ultimoServicioQuery->where('servicio_preventivo_tipo_id', $mantenimiento->servicio_preventivo_tipo_id);
        }

        $ultimoServicio = $ultimoServicioQuery
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->first();

        $lecturaActual = $esMaquina
            ? $horometroService->horometroActual($mantenimiento->maquina)
            : $mantenimiento->km_actuales;

        return [
            'es_maquina' => $esMaquina,
            'equipo' => $esMaquina ? $mantenimiento->maquina?->nombre : trim(($mantenimiento->vehiculo?->marca ?? '') . ' ' . ($mantenimiento->vehiculo?->modelo ?? '')),
            'codigo' => $esMaquina ? $mantenimiento->maquina?->codigo : $mantenimiento->vehiculo?->placas,
            'ubicacion' => $esMaquina
                ? ($mantenimiento->maquina?->ubicacion === Maquina::UBIC_EN_OBRA ? ($mantenimiento->obra?->nombre ?? 'En obra') : 'En patio')
                : ($mantenimiento->obra?->nombre ?? 'No definida'),
            'lectura_actual' => $lecturaActual,
            'lectura_label' => $esMaquina ? 'Horómetro actual' : 'Kilometraje actual',
            'ultimo_servicio' => $ultimoServicio,
        ];
    }
}
