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
use App\Models\MantenimientoMaterial;
use App\Models\Maquina;
use App\Models\OrdenCompra;
use App\Models\Vehiculo;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Mantenimiento\MantenimientoAbastecimientoService;
use App\Services\Contracts\ImageOptimizerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:maquinas.servicios.view.access')->only(['index', 'show']);
        $this->middleware('permission:maquinas.servicios.edit.access')->only(['create', 'store', 'edit', 'update']);
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
    public function store(
        Request $request,
        MaquinaHorometroService $horometroService
    )
    {
        $validated = $request->validate([
            'vehiculo_id' => 'nullable|required_without:maquina_id|exists:vehiculos,id',
            'maquina_id' => 'nullable|required_without:vehiculo_id|exists:maquinas,id',
            'obra_id' => 'nullable|exists:obras,id',
            'tipo' => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'required|string|max:100',
            'servicio_preventivo_tipo_id' => 'nullable|exists:empresa_servicio_preventivo_tipos,id',
            'descripcion' => 'nullable|string|min:3',
            'km_actuales' => 'nullable|integer',
            'km_proximo_servicio' => 'nullable|integer',
            'horometro' => 'nullable|numeric|min:0',
            'detalles' => 'nullable|array',
            'detalles.*.concepto' => 'required|string|max:150',
            'detalles.*.tipo' => 'nullable|in:refaccion,consumible,mano_obra,servicio_externo',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'nullable|numeric|min:0',
            'mecanico_id' => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada' => 'required|date',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ]);

        if (!empty($validated['vehiculo_id']) && !empty($validated['maquina_id'])) {
            return back()
                ->withErrors(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.'])
                ->withInput();
        }

        if (!empty($validated['maquina_id'])) {
            if (!array_key_exists('horometro', $validated) || $validated['horometro'] === null) {
                return back()
                    ->withErrors(['horometro' => 'Captura el horómetro de la máquina al registrar el servicio.'])
                    ->withInput();
            }

            $maquina = Maquina::find($validated['maquina_id']);
            $horometroActual = $maquina ? $horometroService->horometroActual($maquina) : null;

            if ($horometroActual !== null && (float) $validated['horometro'] < (float) $horometroActual) {
                return back()
                    ->withErrors([
                        'horometro' => 'El horómetro del servicio no puede ser menor al horómetro consolidado actual (' . number_format((float) $horometroActual, 2) . ').',
                    ])
                    ->withInput();
            }
        }

        if ($error = $this->validarServicioPreventivoTipo($validated)) {
            return back()->withErrors(['servicio_preventivo_tipo_id' => $error])->withInput();
        }

        // La creación inicia el flujo como borrador; el abastecimiento y la ejecución se deciden después.
        $validated['estatus'] = 'borrador';
        $validated['abastecimiento_estado'] = 'pendiente';

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

        $mantenimiento = Mantenimiento::create($validated);

        if ($detalles->isNotEmpty()) {
            $mantenimiento->detalles()->createMany($detalles->all());

            $mantenimiento->update([
                'costo_mano_obra' => $detalles->where('tipo', 'mano_obra')->sum('costo_total'),
                'costo_refacciones' => $detalles
                    ->whereIn('tipo', ['refaccion', 'consumible'])
                    ->sum('costo_total'),
                'costo_total' => $detalles->sum('costo_total'),
            ]);
        }

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
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['abastecimiento' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('mantenimiento.mantenimientos.edit', $mantenimiento)
            ->with('success', "Solicitud {$solicitud->folio} generada correctamente.");
    }

    public function generarOrdenServicio(Mantenimiento $mantenimiento)
    {
        if ($mantenimiento->estatus !== 'borrador') {
            return back()->with('error', 'La orden de servicio solo puede generarse desde un borrador.');
        }

        if ($mantenimiento->tipo === 'programado' && !$mantenimiento->servicio_preventivo_tipo_id) {
            return back()->withErrors([
                'servicio_preventivo_tipo_id' => 'Selecciona el tipo de servicio preventivo antes de generar la orden.',
            ])->withInput();
        }

        DB::transaction(function () use ($mantenimiento): void {
            $mantenimiento->update([
                'orden_servicio_folio' => sprintf('OSM-%s-%06d', now()->format('y'), $mantenimiento->id),
                'orden_servicio_generada_at' => now(),
                'orden_servicio_generada_por' => auth()->id(),
                'estatus' => 'orden_generada',
            ]);

            if ($mantenimiento->tipo === 'programado' && $mantenimiento->servicio_preventivo_tipo_id) {
                $materiales = collect($this->materialesPreventivosParaMaquina($mantenimiento->maquina_id)[$mantenimiento->servicio_preventivo_tipo_id] ?? []);

                foreach ($materiales as $orden => $material) {
                    MantenimientoMaterial::create([
                        'mantenimiento_id' => $mantenimiento->id,
                        'material_preventivo_id' => $material['id'] ?? null,
                        'producto_id' => $material['producto_id'] ?? null,
                        'producto_nombre_snapshot' => $material['producto'] ?: 'Producto no disponible',
                        'producto_sku_snapshot' => $material['sku'] ?? null,
                        'unidad' => $material['unidad'] ?? null,
                        'cantidad_requerida' => $material['cantidad'] ?? 0,
                        'orden' => $orden,
                        'incluido' => true,
                    ]);
                }
            }
        });

        return back()->with('success', 'Orden de servicio generada correctamente.');
    }

    public function actualizarMaterialesOrden(Request $request, Mantenimiento $mantenimiento)
    {
        if ($mantenimiento->estatus !== 'orden_generada') {
            return back()->withErrors(['materiales' => 'Los materiales solo pueden ajustarse después de generar la orden de servicio y antes de solicitar abastecimiento.']);
        }

        if ($mantenimiento->abastecimientos()->exists()) {
            return back()->withErrors(['materiales' => 'Los materiales ya no pueden modificarse porque existe una solicitud de abastecimiento.']);
        }

        $data = $request->validate([
            'incluidos' => ['nullable', 'array'],
            'incluidos.*' => ['integer', 'exists:mantenimiento_materiales,id'],
            'motivos' => ['nullable', 'array'],
            'motivos.*' => ['nullable', 'string', 'max:255'],
        ]);

        $incluidos = collect($data['incluidos'] ?? [])->map(fn ($id) => (int) $id)->all();
        $motivos = $data['motivos'] ?? [];

        DB::transaction(function () use ($mantenimiento, $incluidos, $motivos): void {
            $mantenimiento->materialesOrden()->get()->each(function (MantenimientoMaterial $material) use ($incluidos, $motivos): void {
                $incluido = in_array($material->id, $incluidos, true);
                $material->update([
                    'incluido' => $incluido,
                    'motivo_exclusion' => $incluido ? null : ($motivos[$material->id] ?? null),
                ]);
            });
        });

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
        ImageOptimizerInterface $imageOptimizer
    )
    {
        $validated = $request->validate([
            'vehiculo_id' => 'nullable|required_without:maquina_id|exists:vehiculos,id',
            'maquina_id' => 'nullable|required_without:vehiculo_id|exists:maquinas,id',
            'obra_id' => 'nullable|exists:obras,id',
            'tipo' => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'servicio_preventivo_tipo_id' => 'nullable|exists:empresa_servicio_preventivo_tipos,id',
            'descripcion' => 'nullable|string',
            'detalles' => 'nullable|array',
            'detalles.*.concepto' => 'required|string|max:150',
            'detalles.*.tipo' => 'nullable|in:refaccion,consumible,mano_obra,servicio_externo',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'nullable|numeric|min:0',
            'km_actuales' => 'nullable|integer',
            'km_proximo_servicio' => 'nullable|integer',
            'horometro' => 'nullable|numeric|min:0',
            'mecanico_id' => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada' => 'nullable|date',
            'fecha_inicio' => 'nullable|required_if:estatus,completado|date',
            'fecha_fin' => 'nullable|required_if:estatus,completado|date|after:fecha_inicio',
            'fotos' => 'nullable|array|max:8',
            'fotos.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_descripciones' => 'nullable|array|max:8',
            'foto_descripciones.*' => 'nullable|string|max:200',
            'estatus' => 'required|in:borrador,orden_generada,pendiente,en_proceso,completado,cancelado',
            'abastecimiento_tipo' => 'nullable|in:surtido_directo,compra,parcial',
            'abastecimiento_estado' => 'nullable|in:pendiente,solicitud_almacen,solicitud_compra,parcial,listo',
        ]);

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

        if (!empty($validated['vehiculo_id']) && !empty($validated['maquina_id'])) {
            return back()
                ->withErrors(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.'])
                ->withInput();
        }

        if (!empty($validated['maquina_id']) && ($validated['horometro'] ?? null) !== null) {
            $maquina = Maquina::find($validated['maquina_id']);
            $horometroActual = $maquina ? $horometroService->horometroActual($maquina) : null;

            if ($horometroActual !== null && (float) $validated['horometro'] < (float) $horometroActual) {
                return back()
                    ->withErrors([
                        'horometro' => 'El horómetro no puede ser menor al horómetro consolidado actual (' . number_format((float) $horometroActual, 2) . ').',
                    ])
                    ->withInput();
            }
        }

        if ($error = $this->validarServicioPreventivoTipo($validated)) {
            return back()->withErrors(['servicio_preventivo_tipo_id' => $error])->withInput();
        }

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

        if (!empty($validated['abastecimiento_tipo']) && ($validated['estatus'] ?? null) !== 'completado') {
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

        foreach ($fotosNuevas as $indice => $foto) {
            $ruta = $imageOptimizer->optimizeAndStore(
                $foto,
                'mantenimientos/evidencias',
                'public',
                ['max_width' => 1920, 'max_height' => 1920, 'quality' => 80, 'format' => 'webp']
            );

            $mantenimiento->fotos()->create([
                'ruta' => $ruta,
                'descripcion' => $request->input("foto_descripciones.{$indice}"),
            ]);
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
        $datos = $this->calcularManoDeObra($mantenimiento);

        if (!$datos) {
            return;
        }

        $detalle = $mantenimiento->detalles()
            ->where('tipo', 'mano_obra')
            ->where('concepto', 'like', 'Mano de obra:%')
            ->first();

        if (!$detalle) {
            $detalle = $mantenimiento->detalles()->make(['tipo' => 'mano_obra']);
        }

        $detalle->fill([
            'concepto' => 'Mano de obra: ' . $datos['nombre'],
            'cantidad' => $datos['horas'],
            'costo_unitario' => $datos['costo_hora'],
            'costo_total' => $datos['costo_total'],
        ]);
        $mantenimiento->detalles()->save($detalle);

        $costoRefacciones = (float) $mantenimiento->detalles()
            ->whereIn('tipo', ['refaccion', 'consumible'])
            ->sum('costo_total');

        $mantenimiento->update([
            'costo_mano_obra' => $datos['costo_total'],
            'costo_refacciones' => round($costoRefacciones, 2),
            'costo_total' => round($datos['costo_total'] + $costoRefacciones, 2),
        ]);
    }

    private function calcularManoDeObra(Mantenimiento $mantenimiento): ?array
    {
        $mantenimiento->loadMissing('mecanico');

        $minutos = $mantenimiento->duracion_en_minutos;
        $mecanico = $mantenimiento->mecanico;
        if ($minutos === null || !$mecanico) {
            return null;
        }

        $sueldo = (float) ($mecanico->Sueldo_real ?: $mecanico->Sueldo ?: 0);
        $diasPeriodo = match ((int) ($mecanico->Sueldo_tipo ?: 0)) {
            1 => 7,
            2 => 15,
            3 => 30,
            default => 0,
        };
        if ($sueldo <= 0 || $diasPeriodo <= 0) {
            return null;
        }

        $horas = round($minutos / 60, 2);
        $costoHora = round($sueldo / $diasPeriodo / 8, 2);

        return [
            'nombre' => trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')) ?: 'Mecánico',
            'horas' => $horas,
            'costo_hora' => $costoHora,
            'costo_total' => round($horas * $costoHora, 2),
        ];
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
        return EmpresaServicioPreventivoTipo::query()
            ->where('ambito', $ambito)
            ->where('activo', true)
            ->ordenados()
            ->get();
    }

    private function materialesPreventivosParaMaquina(?int $maquinaId): array
    {
        if (!$maquinaId) {
            return [];
        }

        $materiales = EmpresaServicioPreventivoMaterial::query()
            ->where('activo', true)
            ->whereHas('servicioPreventivoTipo', function ($query) {
                $query->where('ambito', EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA)
                    ->where('activo', true);
            })
            ->where(function ($query) use ($maquinaId) {
                $query->whereNull('maquina_id')
                    ->orWhere('maquina_id', $maquinaId);
            })
            ->with('producto:id,nombre,sku,unidad')
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->groupBy('servicio_preventivo_tipo_id');

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
                ->get()
                ->keyBy('producto_id')
            : collect();

        return $materiales->map(function ($items) use ($maquinaId, $stockPorProducto, $almacenGiralda) {
            $especificos = $items->where('maquina_id', $maquinaId);
            $efectivos = $especificos->isNotEmpty()
                ? $especificos
                : $items->whereNull('maquina_id');

            return $efectivos->values()->map(function (EmpresaServicioPreventivoMaterial $material) use ($stockPorProducto, $almacenGiralda) {
                $stock = $stockPorProducto->get($material->producto_id);
                $requerido = (float) $material->cantidad_requerida;
                $disponible = max(0, (float) ($stock?->stock_actual ?? 0) - (float) ($stock?->stock_reservado ?? 0));
                $faltante = max(0, $requerido - $disponible);

                return [
                    'id' => $material->id,
                    'producto_id' => $material->producto_id,
                    'producto' => $material->producto?->nombre,
                    'sku' => $material->producto?->sku,
                    'cantidad' => $requerido,
                    'unidad' => $material->unidad ?: $material->producto?->unidad,
                    'especifico' => $material->maquina_id !== null,
                    'disponible' => $disponible,
                    'faltante' => $faltante,
                    'estado' => $faltante <= 0 ? 'disponible' : ($disponible > 0 ? 'parcial' : 'sin_existencia'),
                    'almacen' => $almacenGiralda?->nombre,
                ];
            })->all();
        })->all();
    }

    private function validarServicioPreventivoTipo(array $data): ?string
    {
        $tipoId = $data['servicio_preventivo_tipo_id'] ?? null;

        if (($data['tipo'] ?? null) === 'programado' && !$tipoId) {
            return 'Selecciona el tipo de servicio preventivo para un servicio programado.';
        }

        if (($data['tipo'] ?? null) === 'emergencia' && $tipoId) {
            return 'Un servicio de emergencia no debe tener tipo de servicio preventivo.';
        }

        if (!$tipoId) {
            return null;
        }

        $ambitoEsperado = !empty($data['maquina_id'])
            ? EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA
            : (!empty($data['vehiculo_id']) ? EmpresaServicioPreventivoTipo::AMBITO_VEHICULO : null);

        if (!$ambitoEsperado) {
            return 'Selecciona un vehiculo o una maquina antes de elegir el tipo de servicio.';
        }

        $existe = EmpresaServicioPreventivoTipo::query()
            ->whereKey($tipoId)
            ->where('ambito', $ambitoEsperado)
            ->where('activo', true)
            ->exists();

        return $existe
            ? null
            : 'El tipo de servicio seleccionado no corresponde al activo o no esta activo.';
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
