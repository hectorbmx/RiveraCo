<?php

namespace App\Http\Controllers;

use App\Models\CatalogoPila;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\ObraPlaneacionGasto;
use App\Models\ObraReposicionGasto;
use App\Models\ObraReposicionGastoDetalle;
use App\Models\ObraSolicitudGastoDetalle;
use App\Models\OrdenCompra;
use App\Models\Presupuesto;
use App\Services\Presupuestos\CalculoPresupuestoPorPartidas;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

use Illuminate\Support\Facades\DB;
use App\Models\PresupuestoDetalle;
use App\Models\PresupuestoEvento;
use App\Models\PresupuestoVolPila;
use App\Models\PresupuestoVersion;
use App\Services\Presupuestos\ConsultaPresupuesto;
use App\Services\Presupuestos\PdfPresupuesto;
use App\Support\DatosPresupuestoVersion;
use App\Support\FoliosPresupuesto;
use App\Support\ImagenesTipoPresupuesto;
use App\Support\Inventario\UnidadMedidaCatalogo;
use App\Support\PlantillaApupi;
use App\Support\PresentacionPresupuesto;
use App\Support\PrevioPresupuesto;
use App\Support\PropuestaApupi;
use App\Support\RelacionGastosPresupuesto;
use App\Support\PlaneacionSemanalPresupuesto;
use App\Support\PlaneacionVersionObra;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
class PresupuestoController extends Controller
{
    public function __construct(
        private FoliosPresupuesto $folios,
        private ImagenesTipoPresupuesto $imagenes,
        private ConsultaPresupuesto $consulta,
        private PdfPresupuesto $pdf,
        private CalculoPresupuestoPorPartidas $calculoPorPartidas,
    ) {
    }

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', Rule::in(array_keys(Presupuesto::TIPOS))],
            'duplicar' => ['nullable', 'integer', 'min:1'],
        ], [], ['tipo' => 'tipo de presupuesto']);
        $buscar = trim($filtros['buscar'] ?? '');
        $tipoFiltro = $filtros['tipo'] ?? null;
        $movimientos = PresupuestoEvento::query()
            ->select('presupuesto_eventos.id')
            ->join('presupuesto_versiones', 'presupuesto_versiones.id', '=', 'presupuesto_eventos.presupuesto_version_id')
            ->whereColumn('presupuesto_versiones.presupuesto_id', 'presupuestos.id')
            ->orderByDesc('presupuesto_eventos.id')->limit(1);
        $aceptacion = (clone $movimientos)->where('presupuesto_eventos.tipo', PresupuestoEvento::ACEPTADO)
            ->whereNotExists(function ($posterior) {
                $posterior->selectRaw('1')->from('presupuesto_eventos as posterior')
                    ->whereColumn('posterior.presupuesto_version_id', 'presupuesto_eventos.presupuesto_version_id')
                    ->whereColumn('posterior.id', '>', 'presupuesto_eventos.id');
            });
        $presupuestos = $this->consulta->listado($buscar, $tipoFiltro)
            ->addSelect(['ultimo_movimiento_id' => $movimientos, 'aceptacion_vigente_id' => $aceptacion])
            ->withExists([
                'eventos as tiene_seguimiento' => fn ($eventos) => $eventos->whereIn('tipo', array_keys(PresupuestoEvento::TIPOS_SEGUIMIENTO)),
                'obras',
            ])
            ->with([
                'ultimoMovimiento.version:id,presupuesto_id,revision',
                'aceptacionVigente.version:id,presupuesto_id,revision',
            ])
            ->get();
        $presupuestos->each(function (Presupuesto $presupuesto) {
            $presupuesto->ultimoMovimiento?->version?->setRelation('presupuesto', $presupuesto);
            $presupuesto->aceptacionVigente?->version?->setRelation('presupuesto', $presupuesto);
        });
        $this->consulta->cargarImportes($presupuestos);
        $importes = $presupuestos->mapWithKeys(fn (Presupuesto $p) => [
            $p->id => $this->consulta->importes($p, $this->consulta->versionPreferida($p)),
        ]);

        $origenDuplicacion = isset($filtros['duplicar']) ? Presupuesto::findOrFail($filtros['duplicar']) : null;
        $versionDuplicacion = $origenDuplicacion?->versionParaDuplicar();
        abort_if($origenDuplicacion && ! $versionDuplicacion, 404, 'Guarda una versión para poder duplicar.');
        $nuevoPresupuesto = new Presupuesto(['origen' => Presupuesto::ORIGEN_SIRICO]);
        if ($versionDuplicacion) {
            $versionDuplicacion->setRelation('presupuesto', $origenDuplicacion);
            $nuevoPresupuesto->fill($origenDuplicacion->only(['tipo', 'estado', 'ciudad']) + [
                'cliente_id' => $versionDuplicacion->datos['cliente']['id'] ?? null,
                'nombre_obra' => $versionDuplicacion->datos['presupuesto']['nombre_obra'] ?? null,
                'equipo_propuesto' => $versionDuplicacion->datos['presupuesto']['equipo_propuesto'] ?? null,
            ]);
        }
        $clienteSeleccionado = $this->clienteSeleccionado($request, $nuevoPresupuesto);
        $foliosNuevos = $this->folios->resumen(FoliosPresupuesto::periodoActual());
        $imagenesTipoPresupuesto = $this->imagenes->rutas();

        return view('presupuesto.index', compact('presupuestos', 'buscar', 'tipoFiltro', 'importes', 'nuevoPresupuesto', 'clienteSeleccionado', 'foliosNuevos', 'imagenesTipoPresupuesto', 'origenDuplicacion', 'versionDuplicacion'));
    }

    public function create(Request $request)
    {
        $presupuesto = new Presupuesto(['origen' => Presupuesto::ORIGEN_SIRICO]);

        return view('presupuesto.create', [
            'presupuesto' => $presupuesto,
            'clienteSeleccionado' => $this->clienteSeleccionado($request, $presupuesto),
            'foliosNuevos' => $this->folios->resumen(FoliosPresupuesto::periodoActual()),
            'imagenesTipoPresupuesto' => $this->imagenes->rutas(),
        ]);
    }

    public function buscarClientes(Request $request)
    {
        $datos = $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']]);
        $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $datos['q']);
        $filtro = '%' . $literal . '%';
        $clientes = Cliente::query()->where(function ($query) use ($filtro) {
            $query->whereRaw("nombre_comercial LIKE ? ESCAPE '!'", [$filtro])
                ->orWhereRaw("razon_social LIKE ? ESCAPE '!'", [$filtro])
                ->orWhereRaw("rfc LIKE ? ESCAPE '!'", [$filtro]);
        })->orderBy('nombre_comercial')->orderBy('id')->limit(12)
            ->get(['id', 'nombre_comercial', 'razon_social', 'rfc']);

        return response()->json($clientes);
    }

    public function store(Request $request)
    {
        $presupuesto = DB::transaction(function () use ($request) {
            $presupuesto = new Presupuesto(['origen' => Presupuesto::ORIGEN_SIRICO, 'estatus' => 'borrador']);
            $this->guardarDatos($request, $presupuesto);
            if (in_array($presupuesto->tipo, ['MUROS', 'ANCLAS'], true)) {
                $presupuesto->forceFill(['presentacion_comercial' => ['editor' => 'partidas']])->save();
            }

            return $presupuesto;
        }, 3);

        $destino = $presupuesto->tipo === 'PILAS'
            ? route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            : route('presupuesto.show', $presupuesto) . '#datos-generales';

        return redirect()->to($destino)->with('estadoPresupuesto', 'Presupuesto creado.');
    }

    public function update(Request $request, Presupuesto $presupuesto)
    {
        DB::transaction(function () use ($request, $presupuesto) {
            $this->guardarDatos($request, $this->bloquearBorrador($presupuesto));
        }, 3);

        return $this->volverAlDetalle($request, $presupuesto, 'Datos del presupuesto actualizados.');
    }

    public function duplicar(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('duplicarPresupuesto', ['version' => ['required', 'integer', 'min:1']]);
        $copia = DB::transaction(function () use ($request, $presupuesto, $datos): Presupuesto {
            $origen = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            $version = $origen->versionParaDuplicar();
            if (! $version || $version->id !== (int) $datos['version']) {
                throw ValidationException::withMessages(['version' => 'La versión de origen cambió. Revisa la versión que se muestra antes de duplicar.'])
                    ->errorBag('duplicarPresupuesto');
            }

            if (isset(Presupuesto::TIPOS[$origen->tipo])) {
                $request->merge(['tipo' => $origen->tipo]);
            }
            $copia = new Presupuesto(['origen' => Presupuesto::ORIGEN_SIRICO, 'estatus' => 'borrador']);
            $this->guardarDatos($request, $copia, 'duplicarPresupuesto');

            $captura = $version->datos;
            $captura['presupuesto'] = array_replace($captura['presupuesto'], $copia->only(['nombre_cliente', 'nombre_obra', 'equipo_propuesto']));
            if ((int) ($captura['cliente']['id'] ?? 0) !== (int) $copia->cliente_id) {
                $captura['presupuesto']['presentacion_comercial'] = Arr::except(
                    $captura['presupuesto']['presentacion_comercial'] ?? [], ['atencion', 'telefono', 'correo', 'domicilio']
                );
            }

            DatosPresupuestoVersion::restaurar($copia, $captura);
            $copia->save();
            foreach ($captura['gastos'] ?? [] as $gasto) {
                ObraPlaneacionGasto::create([
                    'presupuesto_id' => $copia->id,
                    'numero_semana' => 0,
                ] + Arr::only($gasto, ['partida', 'concepto', 'unidad', 'cantidad', 'precio_unitario', 'monto_programado']));
            }

            return $copia;
        }, 3);

        $destino = $copia->tipo === 'PILAS'
            ? route('presupuesto.show', [$copia, 'tab' => 'pilas'])
            : route('presupuesto.show', $copia) . '#datos-generales';

        return redirect()->to($destino)->with('estadoPresupuesto', 'Presupuesto duplicado como borrador.');
    }

    public function updatePresentacion(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('presentacionPresupuesto', [
            'domicilio' => ['nullable', 'string', 'max:500'],
            'atencion' => ['nullable', 'string', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:80'],
            'correo' => ['nullable', 'email', 'max:160'],
            'ubicacion' => ['nullable', 'string', 'max:500'],
            'descripcion_perforacion' => ['nullable', 'string', 'max:1000'],
            'anticipo' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,100'],
            'condiciones_pago' => ['nullable', 'string', 'max:1000'],
            'plazo_semanas' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,99999'],
            'iva' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,100'],
            'notas' => ['nullable', 'string', 'max:10000'],
            'descripciones' => ['sometimes', 'array', 'max:300'],
            'descripciones.*' => ['nullable', 'string', 'max:1000'],
            'unidades' => ['sometimes', 'array', 'max:300'],
            'unidades.*' => ['nullable', 'string', 'max:32'],
        ]);
        if (isset($datos['notas'])) {
            $datos['notas'] = PresentacionPresupuesto::notaPorRenglon($datos['notas']);
        }

        DB::transaction(function () use ($presupuesto, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            $anteriores = $actual->presentacion_comercial ?? [];
            $actualizados = array_replace($anteriores, $datos);
            foreach (['descripciones', 'unidades'] as $campo) {
                $valores = array_replace($anteriores[$campo] ?? [], $datos[$campo] ?? []);
                $actualizados[$campo] = array_filter($valores, fn ($texto) => $texto !== null);
            }
            $actual->presentacion_comercial = $actualizados;
            $actual->save();
        }, 3);

        return $this->volverAlDetalle($request, $presupuesto, 'Datos para impresión actualizados.');
    }

    public function updateNotaEjecucion(Request $request, Presupuesto $presupuesto)
    {
        // Los saltos de línea y los espacios entre fragmentos forman parte del Delta de Quill.
        $entrada = $request->validate(['nota_ejecucion' => ['required', 'string', 'max:200000', 'json']]);
        $nota = PresentacionPresupuesto::validarNotaEjecucion($entrada['nota_ejecucion']);

        DB::transaction(function () use ($presupuesto, $nota) {
            $actual = $this->bloquearBorrador($presupuesto);
            $presentacion = $actual->presentacion_comercial ?? [];
            if (!isset($presentacion['nota_ejecucion'])) {
                $presentacion['anticipo_y_plazo_en_notas'] = true;
            }
            $presentacion['nota_ejecucion'] = $nota;
            $actual->presentacion_comercial = $presentacion;
            $actual->save();
        }, 3);

        return response()->json(['ok' => true]);
    }

    public function updateConceptoPresentacion(Request $request, Presupuesto $presupuesto, string $renglon)
    {
        $datos = $request->validate(['concepto' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($presupuesto, $renglon, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            $vol = $actual->calcularVolumetria();
            $duracion = $this->consulta->duracionApupiDesdeVol($actual, $vol);
            $filas = $this->consulta->filasPropuesta($actual, $vol, $duracion)
                ->filter(fn (array $fila) => $fila['cantidad'] > 0 && $fila['precio_unitario'] > 0);
            $captura = $actual->presentacion_comercial ?? [];

            if ($renglon === PresentacionPresupuesto::ENCABEZADO_PERFORACION) {
                abort_unless($filas->contains('partida', 'perforacion'), 404);
                $captura['descripcion_perforacion'] = $datos['concepto'];
            } else {
                abort_unless($filas->has($renglon), 404);
                $captura['descripciones'][$renglon] = $datos['concepto'];
            }

            $actual->presentacion_comercial = $captura;
            $actual->save();
        }, 3);

        return response()->json($datos);
    }

    public function updatePrevio(Request $request, Presupuesto $presupuesto)
    {
        abort_unless($request->user()->hasAnyRole(PrevioPresupuesto::ADMINISTRADORES), 403);
        $porcentaje = ['sometimes', 'required', 'numeric', 'decimal:0,2', 'between:0,99.99'];
        $datos = $request->validateWithBag('previoPresupuesto', [
            'escenario' => ['required', Rule::in(Presupuesto::ESCENARIOS_APUPI)],
            'porcentajes' => ['sometimes', 'array:administracion,imss,comisiones,margen_objetivo'],
            'porcentajes.administracion' => $porcentaje,
            'porcentajes.imss' => $porcentaje,
            'porcentajes.comisiones' => $porcentaje,
            'porcentajes.margen_objetivo' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,99.99'],
            'pendiente' => ['sometimes', 'array:realista,optimista,pesimista'],
            'pendiente.*' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
            'personal' => ['sometimes', 'array:' . implode(',', array_keys(PrevioPresupuesto::PERSONAL))],
            'personal.*' => ['nullable', 'string', 'max:180'],
            'firmas' => ['nullable', 'string', 'max:1000'],
        ]);

        $actual = DB::transaction(function () use ($presupuesto, $datos): Presupuesto {
            $actual = $this->bloquearBorrador($presupuesto);
            $configuracion = array_replace_recursive($actual->datos_previo ?? [], $datos);
            $porcentajes = array_replace(PrevioPresupuesto::PORCENTAJES, $configuracion['porcentajes'] ?? []);
            if (array_sum($porcentajes) >= 100) {
                throw ValidationException::withMessages([
                    'porcentajes.margen_objetivo' => 'Los porcentajes de gastos y el margen objetivo deben sumar menos de 100 %.',
                ])->errorBag('previoPresupuesto');
            }
            $actual->datos_previo = $configuracion;
            $actual->save();

            return $actual;
        }, 3);

        if ($request->expectsJson()) {
            [, $previo] = $this->calcularPrevio($actual);

            return response()->json($previo);
        }

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'previo'])
            ->with('estadoPresupuesto', 'Simulación actualizada.');
    }

    public function aplicarPrevio(Request $request, Presupuesto $presupuesto)
    {
        abort_unless($request->user()->hasAnyRole(PrevioPresupuesto::ADMINISTRADORES), 403);
        $datos = $request->validateWithBag('previoPresupuesto', [
            'escenario' => ['required', Rule::in(Presupuesto::ESCENARIOS_APUPI)],
            'objetivo' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,9999999999999.99'],
        ]);
        $total = DB::transaction(function () use ($presupuesto, $datos): float {
            $actual = $this->bloquearBorrador($presupuesto);
            [$propuesta, $previo] = $this->calcularPrevio($actual);
            $escenario = $previo['escenario'];
            $objetivo = $previo['escenarios'][$escenario]['venta_objetivo'];
            if ($escenario !== $datos['escenario'] || $objetivo === null || (float) $datos['objetivo'] !== $objetivo) {
                throw ValidationException::withMessages([
                    'objetivo' => 'La simulación cambió. Revisa la venta objetivo actual antes de aplicarla.',
                ])->errorBag('previoPresupuesto');
            }

            $filas = collect($propuesta['filas']);
            $precios = PrevioPresupuesto::ajustarPrecios($filas, $objetivo);
            if ($precios === [] || max($precios) > 9999999999999.99) {
                throw ValidationException::withMessages([
                    'objetivo' => 'Para aplicar la venta objetivo necesitas conceptos cobrables con precios dentro del rango de captura.',
                ])->errorBag('previoPresupuesto');
            }

            $ajustes = $actual->ajustes_propuesta_apupi ?? [];
            foreach ($precios as $clave => $precio) {
                $fila = $filas[$clave];
                if ($precio === $fila['precio_unitario']) {
                    continue;
                }
                $anterior = $fila['clave_anterior'];
                $cambios = $ajustes[$clave] ?? $ajustes[$anterior ?? ''] ?? [];
                if ($anterior !== null) {
                    unset($ajustes[$anterior]);
                }
                $cambios['precio_unitario'] = $precio;
                $ajustes[$clave] = $cambios;
            }
            $actual->ajustes_propuesta_apupi = $ajustes ?: null;
            $actual->save();
            [, $resultado] = $this->calcularPrevio($actual);

            return $resultado['venta_actual'];
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'previo'])
            ->with('estadoPresupuesto', 'Propuesta 1 actualizada a $' . number_format($total, 2) . ' sin IVA.');
    }

    private function calcularPrevio(Presupuesto $presupuesto): array
    {
        $vol = $presupuesto->calcularVolumetria();
        $duracion = $this->consulta->duracionApupiDesdeVol($presupuesto, $vol);
        [$costos, $resumen] = $this->consulta->calcularApupi($presupuesto, $vol, $duracion);

        return [$resumen['propuesta'], PrevioPresupuesto::preparar($presupuesto, $costos, $resumen['propuesta'], $duracion)];
    }

    public function updateDuracionApupi(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('duracionApupi', [
            'duracion' => ['required', 'array:realista,optimista,pesimista', 'required_array_keys:realista,optimista,pesimista'],
            'duracion.*' => ['required', 'array:jornales,dias,semanas'],
            'duracion.*.jornales' => ['present', 'nullable', 'integer', 'between:0,999999'],
            'duracion.*.dias' => ['present', 'nullable', 'integer', 'between:0,999999'],
            'duracion.*.semanas' => ['present', 'nullable', 'integer', 'between:0,999999'],
        ], [], [
            'duracion.*.jornales' => 'jornales de trabajo',
            'duracion.*.dias' => 'días calendario',
            'duracion.*.semanas' => 'semanas',
        ]);

        $duracion = [];
        foreach (Presupuesto::ESCENARIOS_APUPI as $escenario) {
            foreach ($datos['duracion'][$escenario] as $campo => $valor) {
                if ($valor !== null) {
                    $duracion[$escenario][$campo] = (int) $valor;
                }
            }
        }
        DB::transaction(function () use ($presupuesto, $duracion) {
            $actual = $this->bloquearBorrador($presupuesto);
            $anterior = $actual->duracion_apupi ?? [];
            if (isset($anterior['equipos'])) {
                $duracion['equipos'] = $anterior['equipos'];
            }
            $actual->duracion_apupi = $duracion ?: null;
            $actual->save();
        }, 3);

        return $this->volverAlDetalle($request, $presupuesto, 'Duración de obra actualizada.');
    }

    public function updateEquiposPlaneacion(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('equiposPlaneacion', [
            'equipos' => ['required', 'integer', 'between:1,999999'],
        ], [], ['equipos' => 'número de equipos']);

        DB::transaction(function () use ($presupuesto, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            $duracion = $actual->duracion_apupi ?? [];
            $duracion['equipos'] = (int) $datos['equipos'];
            $actual->duracion_apupi = $duracion;
            $actual->save();
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'gastos', 'borrador' => 1])
            ->with('estadoPresupuesto', 'Número de equipos actualizado.');
    }

    public function updateCostoApupi(Request $request, Presupuesto $presupuesto, string $detalle)
    {
        $datos = $request->validateWithBag('costoApupi', [
            'cantidad' => ['required_without:precio_unitario', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
            'precio_unitario' => ['required_without:cantidad', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
        ], [], ['precio_unitario' => 'costo unitario']);

        DB::transaction(function () use ($presupuesto, $detalle, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            $vol = $actual->calcularVolumetria();
            $duracion = $this->consulta->duracionApupiDesdeVol($actual, $vol);
            $propuesta = $this->consulta->filasPropuesta($actual, $vol, $duracion);
            $filas = $actual->filasApupi($duracion, PropuestaApupi::totalVenta($propuesta));
            $fila = $filas->get($detalle) ?? (ctype_digit($detalle) ? $filas->firstWhere('detalle.id', '===', (int) $detalle) : null);
            abort_if($fila === null, 404);
            $ajustes = $actual->ajustes_apupi ?? [];
            $cambios = $ajustes[$fila['clave']] ?? $ajustes[$fila['clave_anterior'] ?? ''] ?? [];
            if (isset($fila['clave_anterior'])) {
                unset($ajustes[$fila['clave_anterior']]);
            }
            $original = $fila['detalle'];
            foreach ($datos as $campo => $valor) {
                if ((float) $valor !== (float) $original->{$campo}) {
                    $cambios[$campo] = $valor;
                } else {
                    unset($cambios[$campo]);
                }
            }

            if ($cambios === []) {
                unset($ajustes[$fila['clave']]);
            } else {
                $ajustes[$fila['clave']] = $cambios;
            }

            $actual->ajustes_apupi = $ajustes ?: null;
            $actual->save();
        }, 3);

        return $this->respuestaCostoApupi($request, $presupuesto);
    }

    public function updateIndirectoTrasladoApupi(Request $request, Presupuesto $presupuesto)
    {
        return $this->guardarIndirectoApupi($request, $presupuesto, PresupuestoDetalle::PARTIDA_TRASLADO);
    }

    public function updateIndirectoApupi(Request $request, Presupuesto $presupuesto, string $partida)
    {
        $nombre = collect(PlantillaApupi::partidasConIndirecto())->first(fn (string $nombre) => Str::slug($nombre) === $partida);
        abort_if($nombre === null, 404);

        return $this->guardarIndirectoApupi($request, $presupuesto, $nombre);
    }

    private function guardarIndirectoApupi(Request $request, Presupuesto $presupuesto, string $partida)
    {
        $datos = $request->validateWithBag('indirectoApupi', [
            'porcentaje' => ['required', 'numeric', 'decimal:0,2', 'between:0,999.99'],
        ]);

        DB::transaction(function () use ($presupuesto, $partida, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            if ($partida === PresupuestoDetalle::PARTIDA_TRASLADO) {
                $actual->indirecto_traslado_apupi = $datos['porcentaje'];
            } else {
                $porcentajes = $actual->indirectos_por_partida_apupi ?? [];
                $porcentajes[$partida] = (float) $datos['porcentaje'];
                $actual->indirectos_por_partida_apupi = $porcentajes;
            }
            $actual->save();
        }, 3);

        return $this->respuestaCostoApupi($request, $presupuesto);
    }

    public function updateRenglonPropuestaApupi(Request $request, Presupuesto $presupuesto, string $renglon)
    {
        abort_unless($request->hasAny(['cantidad', 'precio_unitario']), 422);
        $datos = $request->validate([
            'cantidad' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
            'precio_unitario' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
        ], [], ['precio_unitario' => 'precio unitario de venta']);

        DB::transaction(function () use ($presupuesto, $renglon, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            $vol = $actual->calcularVolumetria();
            $duracion = $this->consulta->duracionApupiDesdeVol($actual, $vol);
            $filas = $this->consulta->filasPropuesta($actual, $vol, $duracion);
            $fila = $filas->get($renglon);
            abort_if($fila === null, 404);
            $ajustes = $actual->ajustes_propuesta_apupi ?? [];
            $anterior = $fila['clave_anterior'];
            $cambios = $ajustes[$renglon] ?? $ajustes[$anterior ?? ''] ?? [];
            if ($anterior !== null) {
                unset($ajustes[$anterior]);
            }
            foreach ($datos as $campo => $valor) {
                $valor = $valor === null ? null : (float) $valor;
                if ($valor === $fila['base'][$campo]) {
                    unset($cambios[$campo]);
                } else {
                    $cambios[$campo] = $valor;
                }
            }

            if ($cambios === []) {
                unset($ajustes[$renglon]);
            } else {
                $ajustes[$renglon] = $cambios;
            }
            $actual->ajustes_propuesta_apupi = $ajustes ?: null;
            $actual->save();
        }, 3);

        return $this->respuestaCostoApupi($request, $presupuesto);
    }

    private function respuestaCostoApupi(Request $request, Presupuesto $presupuesto)
    {
        if ($request->expectsJson()) {
            $presupuesto->refresh();
            $vol = $presupuesto->calcularVolumetria();
            $duracion = $this->consulta->duracionApupiDesdeVol($presupuesto, $vol);

            [$costos, $resumen] = $this->consulta->calcularApupi($presupuesto, $vol, $duracion);
            $resumen['presentacion'] = PresentacionPresupuesto::preparar($presupuesto, $costos, $resumen, $duracion);
            $resumen['previo'] = PrevioPresupuesto::preparar($presupuesto, $costos, $resumen['propuesta'], $duracion);
            $resumen['relacion_gastos'] = $this->relacionGastos($presupuesto, $costos, $duracion);

            return response()->json($resumen);
        }

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'apupi'])
            ->with('estadoApupi', 'APUPI actualizado.');
    }

    private function volverAlDetalle(Request $request, Presupuesto $presupuesto, string $mensaje)
    {
        $tab = $request->input('tab');
        return redirect()->route('presupuesto.show', [
            $presupuesto,
            'tab' => in_array($tab, ['pilas', 'apupi', 'presupuesto', 'previo', 'gastos'], true) ? $tab : 'apupi',
        ])->with('estadoPresupuesto', $mensaje);
    }

    private function bloquearBorrador(Presupuesto $presupuesto): Presupuesto
    {
        $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
        abort_if($actual->estaAprobado(), 409, 'El presupuesto aprobado está bloqueado.');

        return $actual;
    }

    private function guardarDatos(Request $request, Presupuesto $presupuesto, string $bolsaErrores = 'datosPresupuesto'): void
    {
        $importado = $presupuesto->esImportado();
        $folioAsignado = $presupuesto->folio_serie_id !== null;
        $folioEditable = $presupuesto->exists && ! $importado && ! $folioAsignado;
        $reglaTipo = $presupuesto->exists
            ? ['sometimes', $presupuesto->tipo === null ? 'nullable' : 'required']
            : ['required'];
        $datos = $request->validateWithBag($bolsaErrores, [
            'tipo' => $folioAsignado ? ['prohibited'] : [...$reglaTipo, Rule::in(array_keys(Presupuesto::TIPOS))],
            'codigo_proyecto' => $folioEditable
                ? ['required', 'string', 'max:255', Rule::unique('presupuestos', 'codigo_proyecto')->ignore($presupuesto)]
                : ['prohibited'],
            'cliente_id' => [$importado ? 'nullable' : 'required', 'integer', Rule::exists('clientes', 'id')],
            'nombre_obra' => [$importado ? 'nullable' : 'required', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'digits:2', Rule::in(array_keys(config('sat_catalogs.estados')))],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'equipo_propuesto' => ['nullable', Rule::in(array_keys(Presupuesto::EQUIPOS_PROPUESTOS))],
        ], [], ['tipo' => 'tipo de presupuesto', 'codigo_proyecto' => 'folio', 'cliente_id' => 'cliente', 'nombre_obra' => 'nombre de obra', 'equipo_propuesto' => 'equipo propuesto']);

        if (! $folioEditable) {
            unset($datos['codigo_proyecto']);
        }
        if ($folioAsignado) {
            unset($datos['tipo']);
        }
        if (! $importado) {
            $datos['nombre_cliente'] = Cliente::findOrFail($datos['cliente_id'])->nombre_comercial;
        }

        try {
            $presupuesto->fill($datos);
            if (! $presupuesto->exists) {
                $this->folios->asignar($presupuesto);
            }
            $presupuesto->save();
        } catch (UniqueConstraintViolationException $e) {
            if ($importado) {
                throw $e;
            }

            $mensaje = $presupuesto->exists ? 'Este folio ya está en uso.' : 'Se ocupó el folio durante el alta. Vuelve a guardar el presupuesto.';
            throw ValidationException::withMessages(['codigo_proyecto' => $mensaje])
                ->errorBag($bolsaErrores);
        }
    }

    private function clienteSeleccionado(Request $request, Presupuesto $presupuesto): ?Cliente
    {
        $id = filter_var($request->old('cliente_id', $presupuesto->cliente_id), FILTER_VALIDATE_INT);

        return $id ? Cliente::find($id, ['id', 'nombre_comercial', 'razon_social', 'rfc']) : null;
    }

    public function show(Request $request, $id)
    {
        // Aquí usamos Eager Loading para traer todas las tablas relacionadas de un golpe
        $presupuesto = Presupuesto::with(['detalles', 'pilas', 'resumenes'])->findOrFail($id);
        if ($presupuesto->estaAprobado()) {
            return redirect()->route('presupuesto.versiones.show', [$presupuesto, $presupuesto->version_aprobada_id]);
        }
        if ($presupuesto->usaEditorPorPartidas()) {
            $estadoEditor = $this->calculoPorPartidas->estadoEditor($presupuesto);
            $unidades = UnidadMedidaCatalogo::opciones() + [
                'FLETE' => 'Flete',
                'HRS' => 'Horas',
                'M' => 'Metro',
                'M3/KM' => 'Metro cúbico por kilómetro',
                'TON' => 'Tonelada',
            ];
            $unidadesGuardadas = collect($estadoEditor['partidas'])->pluck('subpartidas')->collapse()
                ->pluck('unidad')->unique()->diff(array_keys($unidades));

            return view('presupuesto.editor-partidas', [
                'presupuesto' => $presupuesto,
                'estadoEditor' => $estadoEditor,
                'unidades' => $unidades,
                'unidadesGuardadas' => $unidadesGuardadas,
                'clienteSeleccionado' => $this->clienteSeleccionado($request, $presupuesto),
                'imagenesTipoPresupuesto' => $this->imagenes->rutas(),
                'ultimaVersion' => $presupuesto->versiones()->orderByDesc('revision')->first(['id', 'revision']),
            ]);
        }
        $volumetria = $presupuesto->calcularVolumetria();
        $duracionApupi = $this->consulta->duracionApupiDesdeVol($presupuesto, $volumetria);
        $editarVol = $volumetria['filas']->firstWhere('pila.id', $request->integer('editar_pila'))['pila'] ?? null;
        $diametrosVol = $this->diametrosPilasVol();

        $clienteSeleccionado = $this->clienteSeleccionado($request, $presupuesto);
        [$filasApupi, $resumenApupi] = $this->consulta->calcularApupi($presupuesto, $volumetria, $duracionApupi);
        $relacionGastos = $this->relacionGastos($presupuesto, $filasApupi, $duracionApupi);
        $presentacion = PresentacionPresupuesto::preparar($presupuesto, $filasApupi, $resumenApupi, $duracionApupi);
        $previo = PrevioPresupuesto::preparar($presupuesto, $filasApupi, $resumenApupi['propuesta'], $duracionApupi);
        $versiones = $presupuesto->versiones()->orderByDesc('revision')->limit(2)->get(['id', 'revision']);
        $versionGuardada = $versiones->first();
        $presupuesto->versiones_count = $versiones->count();
        $versionUnica = $this->consulta->versionComercialUnica($presupuesto);
        $versionUnica?->setRelation('presupuesto', $presupuesto);

        $imagenesTipoPresupuesto = $this->imagenes->rutas();

        return view('presupuesto.show', compact('presupuesto', 'relacionGastos', 'volumetria', 'editarVol', 'diametrosVol', 'clienteSeleccionado', 'duracionApupi', 'filasApupi', 'resumenApupi', 'versionGuardada', 'versionUnica', 'presentacion', 'previo', 'imagenesTipoPresupuesto'));
    }

    public function storeVersion(Request $request, Presupuesto $presupuesto)
    {
        abort_if($presupuesto->estaAprobado(), 409, 'El presupuesto aprobado está bloqueado.');
        $datos = $request->validateWithBag('versionPresupuesto', [
            'revision' => ['sometimes', 'required', 'integer', 'min:0'],
            'revision_partidas' => [Rule::requiredIf($presupuesto->usaEditorPorPartidas()), 'nullable', 'string', 'size:64'],
        ]);
        $revision = (int) ($datos['revision'] ?? 0);
        [$version, $creada] = DB::transaction(function () use ($request, $presupuesto, $revision, $datos): array {
            if ($revision === 0 && $presupuesto->consecutivo_cliente === null) {
                try {
                    (clone $presupuesto)->asignarConsecutivoCliente();
                } catch (ValidationException $e) {
                    throw $e->errorBag('versionPresupuesto');
                }
            }

            $actual = $this->bloquearBorrador($presupuesto);
            $guardada = $actual->versiones()->where('revision', $revision)->lockForUpdate()->first(['id']);
            if ($guardada !== null) {
                return [$guardada, false];
            }

            $ultima = $actual->versiones()->orderByDesc('revision')->first(['id', 'revision']);
            $siguiente = $ultima ? $ultima->revision + 1 : 0;
            if ($revision !== $siguiente) {
                throw ValidationException::withMessages([
                    'revision' => 'El número de versión cambió. Recarga el presupuesto para guardar la siguiente versión.',
                ])->errorBag('versionPresupuesto');
            }

            $origenBorrador = $actual->version_origen_borrador_id;
            if ($actual->usaEditorPorPartidas()) {
                $estado = $this->calculoPorPartidas->estadoEditor($actual);
                if ($estado['revision'] !== ($datos['revision_partidas'] ?? null)) {
                    throw ValidationException::withMessages([
                        'revision_partidas' => 'Las partidas o las notas cambiaron. Recarga el presupuesto antes de guardar la versión.',
                    ])->errorBag('versionPresupuesto');
                }
                $presentacion = $this->calculoPorPartidas->presentacion($actual);
                $totalVenta = $presentacion['subtotal'];
                if (\Brick\Math\BigDecimal::of($totalVenta)->isGreaterThan('9999999999999.99')) {
                    throw ValidationException::withMessages([
                        'revision_partidas' => 'El subtotal supera el límite permitido para una versión.',
                    ])->errorBag('versionPresupuesto');
                }
                $captura = DatosPresupuestoVersion::capturarPorPartidas($actual, $presentacion);
            } else {
                $actual->load(['cliente', 'detalles', 'pilas', 'resumenes']);
                $vol = $actual->calcularVolumetria();
                $duracion = $this->consulta->duracionApupiDesdeVol($actual, $vol);
                [$costos, $resumen] = $this->consulta->calcularApupi($actual, $vol, $duracion);
                $captura = DatosPresupuestoVersion::capturar($actual, $vol, $duracion, $costos, $resumen, $this->gastosBase($actual));
                $totalVenta = $resumen['propuesta']['total'];
            }
            $autor = $request->user()->getKey();
            $version = $actual->versiones()->create([
                'revision' => $revision,
                'version_origen_id' => $origenBorrador ?? $ultima?->id,
                'motivo' => $actual->motivo_borrador_revision,
                'creado_por' => $autor,
                'datos' => $captura,
                'total_venta' => $totalVenta,
            ]);
            $version->eventos()->create(['tipo' => PresupuestoEvento::GUARDADO, 'registrado_por' => $autor]);
            if ($origenBorrador !== null) {
                $actual->forceFill(['version_origen_borrador_id' => null, 'motivo_borrador_revision' => null])->save();
            }

            return [$version, true];
        }, 3);

        $mensaje = $creada
            ? "Versión {$version->folio()} guardada. Puedes seguir editando el borrador."
            : 'Ese número de versión ya estaba guardado. Puedes guardar el borrador actual como la siguiente versión.';

        return redirect()->route('presupuesto.show', $presupuesto->usaEditorPorPartidas()
            ? [$presupuesto] : [$presupuesto, 'tab' => 'apupi', 'borrador' => 1])
            ->with('estadoPresupuesto', $mensaje);
    }

    public function historialVersiones(Presupuesto $presupuesto)
    {
        $columnas = ['id', 'presupuesto_id', 'revision', 'creado_por', 'motivo', 'total_venta', 'created_at'];
        if ($presupuesto->usaEditorPorPartidas()) {
            $columnas[] = 'datos';
        }
        $versiones = $presupuesto->versiones()->with(['autor:id,name', 'ultimoEvento'])->orderByDesc('revision')
            ->get($columnas);
        $versiones->each(fn (PresupuestoVersion $version) => $version->setRelation('presupuesto', $presupuesto));

        return view('presupuesto.versiones', compact('presupuesto', 'versiones'));
    }

    public function crearRevision(Request $request, Presupuesto $presupuesto, int $version)
    {
        $datos = $request->validateWithBag('revisionPresupuesto', [
            'ultima_revision' => ['required', 'integer', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:2000'],
        ]);
        $creado = DB::transaction(function () use ($presupuesto, $version, $datos): bool {
            $actual = $this->bloquearBorrador($presupuesto);
            $origen = $actual->versiones()->findOrFail($version);
            if ($actual->version_origen_borrador_id !== null) {
                return false;
            }
            if ((int) $datos['ultima_revision'] !== (int) $actual->versiones()->max('revision')) {
                throw ValidationException::withMessages([
                    'ultima_revision' => 'Hay una versión más reciente. Actualiza el historial y vuelve a elegir la versión de origen.',
                ])->errorBag('revisionPresupuesto');
            }

            DatosPresupuestoVersion::restaurar($actual, $origen->datos);
            $actual->forceFill([
                'version_origen_borrador_id' => $origen->id,
                'motivo_borrador_revision' => $datos['motivo'] ?? null,
            ])->save();

            return true;
        }, 3);

        return redirect()->route('presupuesto.show', $presupuesto->usaEditorPorPartidas()
            ? [$presupuesto] : [$presupuesto, 'tab' => 'pilas', 'borrador' => 1])
            ->with('estadoPresupuesto', $creado ? 'Borrador de revisión creado.' : 'Ya hay un borrador de revisión en curso.');
    }

    public function showVersion(Presupuesto $presupuesto, int $version)
    {
        $version = $presupuesto->versiones()->with([
            'autor:id,name',
            'origen:id,presupuesto_id,revision',
            'eventos' => fn ($eventos) => $eventos->with('autor:id,name')->latest('id'),
        ])->findOrFail($version);
        $version->setRelation('presupuesto', $presupuesto);
        $version->origen?->setRelation('presupuesto', $presupuesto);

        $obrasPlaneacion = $presupuesto->obras()->withPivot('planeacion_version_id')
            ->orderBy('obras.nombre')->get(['obras.id', 'obras.nombre'])->unique('id');

        $imagenesTipoPresupuesto = $this->imagenes->rutas();

        return view('presupuesto.version', compact('presupuesto', 'version', 'obrasPlaneacion', 'imagenesTipoPresupuesto'));
    }

    public function aplicarPlaneacionSemanal(Request $request, Presupuesto $presupuesto, int $version)
    {
        abort_unless($request->user()->hasAnyRole(PrevioPresupuesto::ADMINISTRADORES), 403);
        $datos = $request->validateWithBag('planeacionSemanal', ['obra_id' => ['required', 'integer']]);
        $guardada = $presupuesto->versiones()->findOrFail($version);
        $relacion = $guardada->datos['relacion_gastos'] ?? [];
        abort_unless($relacion['planeacion']['publicable'] ?? false, 422, 'La versión requiere una programación semanal completa.');
        $filas = PlaneacionVersionObra::filas($relacion, 'obra_id', 'planeacionSemanal');
        $error = fn (string $mensaje) => ValidationException::withMessages(['obra_id' => $mensaje])->errorBag('planeacionSemanal');

        $obra = DB::transaction(function () use ($presupuesto, $guardada, $datos, $filas, $error): Obra {
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            if ($actual->estaAprobado()
                && ((int) $actual->version_aprobada_id !== $guardada->id || (int) $actual->obra_generada_id !== (int) $datos['obra_id'])) {
                throw $error('La planeación debe corresponder a la versión aprobada y a su obra.');
            }
            $obra = Obra::lockForUpdate()->findOrFail($datos['obra_id']);
            $vinculo = DB::table('obra_presupuesto')->where('obra_id', $obra->id)->where('presupuesto_id', $presupuesto->id);
            $versiones = (clone $vinculo)->lockForUpdate()->pluck('planeacion_version_id');
            if ($versiones->isEmpty()) {
                throw $error('Vincula el presupuesto con la obra antes de aplicar su planeación.');
            }
            if ($versiones->contains($guardada->id)) {
                return $obra;
            }
            if ($versiones->filter()->isNotEmpty()) {
                throw $error('Esta obra ya tiene una versión aplicada de este presupuesto.');
            }

            $base = ObraPlaneacionGasto::query()->where('presupuesto_id', $presupuesto->id)->porSemana(0);
            $ids = $base->pluck('id');
            $conceptos = $filas->mapWithKeys(fn (array $fila) => [Str::slug($fila['concepto']) => true]);
            $locales = $obra->planeacionGastos()->porSemana(0)->pluck('concepto');
            if ($locales->contains(fn ($concepto) => $conceptos->has(Str::slug($concepto)))
                || $base->whereHas('semanas', fn ($q) => $q->where('monto_programado', '<>', 0))->exists()
                || OrdenCompra::whereIn('planeacion_gasto_id', $ids)->exists()
                || ObraSolicitudGastoDetalle::whereIn('planeacion_gasto_id', $ids)->exists()
                || ObraReposicionGasto::whereIn('partida_id', $ids)->exists()
                || ObraReposicionGastoDetalle::whereIn('partida_id', $ids)->exists()) {
                throw $error('Hay conceptos que ya tienen planeación o movimientos en Obra. Revisa la planeación vigente antes de aplicar esta versión.');
            }

            PlaneacionVersionObra::copiar($obra, $filas);
            $vinculo->update(['planeacion_version_id' => $guardada->id]);

            return $obra;
        }, 3);

        return redirect()->route('obras.edit', [$obra, 'tab' => 'planeacion'])
            ->with('success', 'Planeación de la versión aplicada a la obra.');
    }

    public function storeSeguimiento(Request $request, Presupuesto $presupuesto, int $version)
    {
        $datos = $request->validateWithBag('seguimientoPresupuesto', [
            'tipo' => ['required', Rule::in(array_keys(PresupuestoEvento::TIPOS_SEGUIMIENTO))],
            'nota' => ['nullable', 'string', 'max:2000'],
        ]);
        $autor = $request->user()->getKey();
        DB::transaction(function () use ($presupuesto, $version, $datos, $autor) {
            $actual = $this->bloquearBorrador($presupuesto);
            $guardada = $actual->versiones()->lockForUpdate()->findOrFail($version, ['id']);
            $ultimo = $guardada->eventos()->latest('id')->first();
            $nota = $datos['nota'] ?? null;
            if ($ultimo?->tipo === $datos['tipo'] && $ultimo->nota === $nota && (int) $ultimo->registrado_por === (int) $autor) {
                return;
            }

            $guardada->eventos()->create([
                'tipo' => $datos['tipo'],
                'nota' => $nota,
                'registrado_por' => $autor,
            ]);
        }, 3);

        return redirect()->to(route('presupuesto.versiones.show', [$presupuesto, $version]) . '#seguimiento')
            ->with('estadoSeguimiento', 'Movimiento registrado.');
    }

    private function gastosBase(Presupuesto $presupuesto): Collection
    {
        if ($presupuesto->version_origen_borrador_id !== null) {
            return collect($presupuesto->origenBorrador->datos['gastos'])->map(fn (array $fila) => new ObraPlaneacionGasto($fila));
        }

        return ObraPlaneacionGasto::where('presupuesto_id', $presupuesto->id)
            ->where('numero_semana', 0)->orderBy('id')->get();
    }

    private function relacionGastos(Presupuesto $presupuesto, Collection $costos, array $duracion): array
    {
        return RelacionGastosPresupuesto::preparar(
            $this->gastosBase($presupuesto), $costos, $duracion, $presupuesto->ajustes_gastos_semanales ?? [],
        );
    }

    public function updateGastoSemanal(Request $request, Presupuesto $presupuesto)
    {
        $reglas = [
            'concepto' => ['required', 'string', 'max:70'],
            'semana' => ['required', 'integer', 'min:1', 'max:' . PlaneacionSemanalPresupuesto::LIMITE_SEMANAS],
            'monto' => ['present', 'nullable', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
        ];
        $lote = $request->has('cambios');
        $restablecer = $request->boolean('restablecer');
        $validacion = match (true) {
            $lote => [
                'cambios' => ['bail', 'required', 'array', function ($atributo, $valor, $fallar) {
                    if (! array_is_list($valor)) {
                        $fallar('Envía una lista de cambios semanales.');
                    }
                }],
                'cambios.*' => ['required', 'array:concepto,semana,monto'],
            ]
                + collect($reglas)->mapWithKeys(fn (array $regla, string $campo) => ['cambios.*.' . $campo => $regla])->all(),
            $restablecer => [],
            default => $reglas,
        };
        $datos = $request->validate($validacion + ['restablecer' => ['sometimes', 'accepted']]);
        $cambios = $lote ? $datos['cambios'] : ($restablecer ? [] : [$datos]);

        $relacion = DB::transaction(function () use ($presupuesto, $cambios, $restablecer): array {
            $actual = $this->bloquearBorrador($presupuesto);
            $vol = $actual->calcularVolumetria();
            $duracion = $this->consulta->duracionApupiDesdeVol($actual, $vol);
            [$costos] = $this->consulta->calcularApupi($actual, $vol, $duracion);
            $base = $this->gastosBase($actual);
            $ajustes = $restablecer ? [] : ($actual->ajustes_gastos_semanales ?? []);
            if ($cambios !== []) {
                $anterior = RelacionGastosPresupuesto::preparar($base, $costos, $duracion, $ajustes);
                $filas = collect($anterior['partidas'])->pluck('filas')->collapse()->keyBy('id');
                $semanas = array_column($anterior['planeacion']['semanas'], 'jornales', 'numero');
                foreach ($cambios as $cambio) {
                    $this->ajustarGastoSemanal($ajustes, $filas->get($cambio['concepto']), $semanas, $cambio);
                }
            }

            $actual->ajustes_gastos_semanales = $ajustes ?: null;
            $actual->save();

            return RelacionGastosPresupuesto::preparar($base, $costos, $duracion, $ajustes);
        }, 3);

        return response()->json($relacion);
    }

    private function ajustarGastoSemanal(array &$ajustes, ?array $fila, array $semanas, array $cambio): void
    {
        $semana = (int) $cambio['semana'];
        if ($fila === null || ! $fila['planeacion']['editable'] || ! array_key_exists($semana, $semanas)) {
            throw ValidationException::withMessages(['monto' => 'Este concepto o semana ya no está disponible para programar.']);
        }
        $id = $fila['id'];
        $tope = PlaneacionSemanalPresupuesto::centavos($fila['monto_programado'] ?? 0);
        $monto = $cambio['monto'] === null ? null : PlaneacionSemanalPresupuesto::centavos($cambio['monto']);
        $concepto = $fila['concepto'] . ' · Semana ' . $semana;
        if ($monto !== null && $monto > $tope) {
            throw ValidationException::withMessages(['monto' => $concepto . '. El importe supera el monto previsto.']);
        }
        if ($monto !== null && count($semanas) === 1 && $monto !== $tope) {
            throw ValidationException::withMessages(['monto' => $concepto . '. La única semana debe cubrir todo el importe previsto.']);
        }
        $ajustes[$id] = PlaneacionSemanalPresupuesto::ajustarSemana($tope, $semanas, $ajustes[$id] ?? [], $semana, $monto);
        if ($ajustes[$id] === []) {
            unset($ajustes[$id]);
        }
    }

    public function storePilaVol(Request $request, Presupuesto $presupuesto)
    {
        $datos = $this->validarPilaVol($request);
        DB::transaction(function () use ($presupuesto, $datos) {
            $this->bloquearBorrador($presupuesto)->pilasVol()->create($datos);
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoVol', 'Tipo de pila agregado.');
    }

    public function updatePilaVol(Request $request, Presupuesto $presupuesto, int $pilaVol)
    {
        DB::transaction(function () use ($request, $presupuesto, $pilaVol) {
            $pila = $this->bloquearBorrador($presupuesto)->pilasVol()->findOrFail($pilaVol);
            $pila->update($this->validarPilaVol($request, anterior: $pila));
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoVol', 'Tipo de pila actualizado.');
    }

    public function updateTraslapeVol(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('traslapeVol', [
            'vol_traslape_varilla' => ['required', 'numeric', 'decimal:0,5', 'between:0,11.99999'],
        ], [
            'vol_traslape_varilla.between' => 'El traslape por empalme debe ser mayor o igual a 0 y menor a 12 m.',
        ]);
        DB::transaction(function () use ($presupuesto, $datos) {
            $actual = $this->bloquearBorrador($presupuesto);
            $actual->vol_traslape_varilla = $datos['vol_traslape_varilla'];
            $actual->save();
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoVol', 'Traslape de varilla actualizado.');
    }

    public function previewPilaVol(Request $request, Presupuesto $presupuesto)
    {
        $idPila = $request->integer('pila_vol');
        $anterior = $idPila ? $presupuesto->pilasVol()->findOrFail($idPila) : null;
        $pila = new PresupuestoVolPila($this->validarPilaVol($request, permitirIncompleto: true, anterior: $anterior));

        return response()->json($pila->volumetria((float) $presupuesto->vol_traslape_varilla));
    }

    private function diametrosPilasVol(): Collection
    {
        return CatalogoPila::where('activa', true)
            ->where('diametro_cm', '>=', 15)
            ->distinct()
            ->orderBy('diametro_cm')
            ->pluck('diametro_cm')
            ->map(fn ($diametro) => number_format($diametro / 100, 3, '.', ''));
    }

    private function validarPilaVol(Request $request, bool $permitirIncompleto = false, ?PresupuestoVolPila $anterior = null): array
    {
        $captura = $permitirIncompleto ? 'nullable' : 'required';
        $reglasCalibreZuncho = ['nullable', Rule::in(array_keys(PresupuestoVolPila::TRASLAPES_METROS))];
        $datos = $request->validateWithBag('pilaVol', [
            'tipo' => [$captura, 'string', 'max:80'],
            'diametro' => ['bail', $captura, 'numeric', 'decimal:0,3', 'between:0.15,99999.999',
                function ($atributo, $valor, $fallar) use ($anterior): void {
                    if ($anterior && (float) $valor === (float) $anterior->diametro) {
                        return;
                    }
                    if (!$this->diametrosPilasVol()->containsStrict(number_format((float) $valor, 3, '.', ''))) {
                        $fallar('Selecciona un diámetro activo del catálogo.');
                    }
                },
            ],
            'piezas' => [$captura, 'integer', 'between:1,4294967295'],
            'longitud_l0' => [$captura, 'numeric', 'decimal:0,3', 'between:0.001,99999.999'],
            'avance_metros_por_jornal' => ['sometimes', $captura, 'numeric', 'decimal:0,5', 'between:0.00001,999999999.99999'],
            'longitud_l1' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'longitud_l2' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'intervalo_l0' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'intervalo_l1' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'intervalo_l2' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'calibre_zuncho_l0' => $reglasCalibreZuncho,
            'calibre_zuncho_l1' => $reglasCalibreZuncho,
            'calibre_zuncho_l2' => $reglasCalibreZuncho,
            'varillas_l0' => ['nullable', 'integer', 'between:0,4294967295'],
            'varillas_l1' => ['nullable', 'integer', 'between:0,4294967295'],
            'calibre_varilla_l0' => ['nullable', Rule::in(array_keys(PresupuestoVolPila::PESOS_VARILLA))],
            'calibre_varilla_l1' => ['nullable', Rule::in(array_keys(PresupuestoVolPila::PESOS_VARILLA))],
            'acero_kg_por_pila' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,9999999.999'],
        ], [], [
            'longitud_l0' => 'longitud L0',
            'avance_metros_por_jornal' => 'avance en metros por jornal',
            'calibre_zuncho_l0' => 'calibre de zuncho L0',
            'calibre_zuncho_l1' => 'calibre de zuncho L1',
            'calibre_zuncho_l2' => 'calibre de zuncho L2',
            'calibre_varilla_l0' => 'calibre de varilla L0',
            'calibre_varilla_l1' => 'calibre de varilla L1',
        ]);

        foreach (['longitud_l1', 'longitud_l2', 'intervalo_l0', 'intervalo_l1', 'intervalo_l2', 'varillas_l0', 'varillas_l1'] as $campo) {
            $datos[$campo] ??= 0;
        }
        $datos['avance_metros_por_jornal'] ??= $anterior
            ? max(0.00001, round($anterior->volumetria()['avance_metros_por_jornal'], 5))
            : PresupuestoVolPila::AVANCE_PREDETERMINADO;
        foreach (['calibre_zuncho_l0', 'calibre_zuncho_l1', 'calibre_zuncho_l2'] as $campo) {
            if (! array_key_exists($campo, $datos)) {
                $datos[$campo] = $anterior?->{$campo};
            }
        }
        foreach (['calibre_varilla_l0', 'calibre_varilla_l1'] as $campo) {
            $datos[$campo] ??= $anterior ? $anterior->{$campo} : PresupuestoVolPila::CALIBRE_PREDETERMINADO;
            if ($datos[$campo] !== null) {
                $datos[$campo] = (string) $datos[$campo];
            }
        }
        $datos['acero_kg_por_pila'] ??= null;
        // Una pila existente conserva la longitud exacta calculada hasta que se edita su geometría.
        $mismaGeometria = $anterior?->volumen_objetivo !== null
            && isset($datos['diametro'], $datos['longitud_l0'])
            && (float) $datos['diametro'] === (float) $anterior->diametro
            && (float) $datos['longitud_l0'] === (float) $anterior->longitud_l0;
        $datos['volumen_objetivo'] = $mismaGeometria ? $anterior->volumen_objetivo : null;
        if (! $permitirIncompleto) {
            $vol = (new PresupuestoVolPila($datos))->volumetria();
            if ($vol['pilas_por_jornal'] > 99999.999) {
                throw ValidationException::withMessages([
                    'avance_metros_por_jornal' => 'Las pilas por jornal calculadas superan el rango de captura.',
                ])->errorBag('pilaVol');
            }
            $datos['pilas_por_jornal'] = round($vol['pilas_por_jornal'], 3);
        }

        return $datos;
    }

    public function destroyPilaVol(Presupuesto $presupuesto, int $pilaVol)
    {
        DB::transaction(function () use ($presupuesto, $pilaVol) {
            $this->bloquearBorrador($presupuesto)->pilasVol()->findOrFail($pilaVol)->delete();
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoVol', 'Tipo de pila eliminado.');
    }
    public function exportVersionPdf(Presupuesto $presupuesto, int $version)
    {
        $version = $presupuesto->versiones()->findOrFail($version);
        $presentacion = $this->consulta->presentacionVersion($version);
        if (isset($presentacion['partidas']) && ! PresentacionPresupuesto::tieneConceptos($presentacion)) {
            return redirect()->route('presupuesto.versiones.show', [$presupuesto, $version])
                ->with('errorPdfPresupuesto', 'La versión no contiene subpartidas para exportar.');
        }

        return $this->pdf->deVersion($presupuesto, $version, $presentacion);
    }

    public function exportPdf(Request $request, $id)
    {
        $presupuesto = Presupuesto::with(['detalles', 'resumenes', 'pilas', 'cliente'])->findOrFail($id);
        if ($presupuesto->estaAprobado()) {
            return $this->exportVersionPdf($presupuesto, $presupuesto->version_aprobada_id);
        }
        if (!$request->boolean('borrador')) {
            if ($version = $this->consulta->versionPreferida($presupuesto)) {
                return $this->exportVersionPdf($presupuesto, $version->id);
            }
        }
        $presentacion = $this->consulta->borradorComercial($presupuesto)['presentacion'];
        if (! PresentacionPresupuesto::tieneConceptos($presentacion)) {
            if ($presupuesto->usaEditorPorPartidas()) {
                return redirect()->route('presupuesto.show', $presupuesto)
                    ->with('errorPdfPresupuesto', 'Agrega y guarda una subpartida antes de exportar el PDF.');
            }
            return redirect()->to(route('presupuesto.show', [$presupuesto, 'tab' => 'apupi', 'borrador' => 1]) . '#propuesta-apupi')
                ->with('errorPdfPresupuesto', 'Para exportar el PDF, guarda una cantidad y un precio unitario de venta en Propuesta 1.');
        }

        return $this->pdf->delPresupuesto($presupuesto, $presentacion);
    }

    public function destroy($id)
    {
        try {
            $error = DB::transaction(function () use ($id): ?string {
                $presupuesto = Presupuesto::lockForUpdate()->findOrFail($id);

                if (!$presupuesto->esBorrador()) {
                    return 'Solo se pueden eliminar presupuestos en estado de borrador.';
                }

                if ($presupuesto->eventos()->whereIn('tipo', array_keys(PresupuestoEvento::TIPOS_SEGUIMIENTO))->exists()) {
                    return 'Este presupuesto ya tiene seguimiento comercial.';
                }

                if ($presupuesto->obras()->exists()) {
                    return 'Este presupuesto está vinculado a una obra.';
                }

                if ($presupuesto->version_origen_borrador_id !== null) {
                    $presupuesto->forceFill(['version_origen_borrador_id' => null])->save();
                }

                $versiones = $presupuesto->versiones()->orderByDesc('revision')->get(['id']);
                foreach ($versiones as $version) {
                    $version->eventos()->delete();
                    // Las versiones se eliminan juntas al borrar el borrador completo.
                    $presupuesto->versiones()->whereKey($version->id)->delete();
                }

                $presupuesto->delete();

                return null;
            }, 3);
        } catch (QueryException $e) {
            report($e);
            $error = 'Ocurrió un error al eliminar el presupuesto.';
        }

        return redirect()->route('presupuesto.index')
            ->with($error === null ? 'success' : 'error', $error ?? 'Presupuesto eliminado correctamente.');
    }
}
