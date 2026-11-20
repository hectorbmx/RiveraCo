<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Obra;
use App\Models\ObraPlaneacionGasto;
use App\Models\ObraReposicionGasto;
use App\Models\ObraReposicionGastoDetalle;
use App\Models\ObraSolicitudGastoDetalle;
use App\Models\OrdenCompra;
use App\Models\Presupuesto;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;

use Illuminate\Support\Facades\DB;
use App\Models\PresupuestoDetalle;
use App\Models\PresupuestoEvento;
use App\Models\PresupuestoPila;
use App\Models\PresupuestoResumen;
use App\Models\PresupuestoVolPila;
use App\Models\PresupuestoVersion;
use App\Support\DatosPresupuestoVersion;
use App\Support\PlantillaApupi;
use App\Support\PresentacionPresupuesto;
use App\Support\PrevioPresupuesto;
use App\Support\PropuestaApupi;
use App\Support\RelacionGastosPresupuesto;
use App\Support\PlaneacionSemanalPresupuesto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
class PresupuestoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['buscar' => ['nullable', 'string', 'max:255']]);
        $buscar = trim((string) $request->input('buscar', ''));
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
        $presupuestos = Presupuesto::query()
            ->addSelect(['ultimo_movimiento_id' => $movimientos, 'aceptacion_vigente_id' => $aceptacion])
            ->with([
                'cliente:id,nombre_comercial',
                'ultimoMovimiento.version:id,presupuesto_id,revision',
                'aceptacionVigente.version:id,presupuesto_id,revision',
            ])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar);
                $query->where(function ($query) use ($literal) {
                    $query->whereRaw("nombre_cliente LIKE ? ESCAPE '!'", ['%' . $literal . '%'])
                        ->orWhereRaw("codigo_proyecto LIKE ? ESCAPE '!'", ['%' . $literal . '%'])
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->whereRaw(
                            "nombre_comercial LIKE ? ESCAPE '!'", ['%' . $literal . '%']
                        ));
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
        $presupuestos->each(function (Presupuesto $presupuesto) {
            $presupuesto->ultimoMovimiento?->version?->setRelation('presupuesto', $presupuesto);
            $presupuesto->aceptacionVigente?->version?->setRelation('presupuesto', $presupuesto);
        });
        
        return view('presupuesto.index', compact('presupuestos', 'buscar'));
    }
    public function create(Request $request)
    {
        $presupuesto = new Presupuesto(['origen' => Presupuesto::ORIGEN_SIRICO]);

        return view('presupuesto.create', [
            'presupuesto' => $presupuesto,
            'clienteSeleccionado' => $this->clienteSeleccionado($request, $presupuesto),
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
        $presupuesto = new Presupuesto(['origen' => Presupuesto::ORIGEN_SIRICO, 'estatus' => 'borrador']);
        $this->guardarDatos($request, $presupuesto);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoPresupuesto', 'Presupuesto creado.');
    }

    public function update(Request $request, Presupuesto $presupuesto)
    {
        DB::transaction(function () use ($request, $presupuesto) {
            $this->guardarDatos($request, Presupuesto::lockForUpdate()->findOrFail($presupuesto->id));
        }, 3);

        return $this->volverAlDetalle($request, $presupuesto, 'Datos del presupuesto actualizados.');
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
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
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

    public function updateConceptoPresentacion(Request $request, Presupuesto $presupuesto, string $renglon)
    {
        $datos = $request->validate(['concepto' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($presupuesto, $renglon, $datos) {
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            $vol = $actual->calcularVolumetria();
            $duracion = $actual->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
            $filas = $this->filasPropuesta($actual, $vol, $duracion)
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
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
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
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
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
        $duracion = $presupuesto->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
        [$costos, $resumen] = $this->calcularApupi($presupuesto, $vol, $duracion);

        return [$resumen['propuesta'], PrevioPresupuesto::preparar($presupuesto, $costos, $resumen['propuesta'], $duracion)];
    }

    public function updateDuracionApupi(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('duracionApupi', [
            'duracion' => ['required', 'array:realista,optimista,pesimista', 'required_array_keys:realista,optimista,pesimista'],
            'duracion.*' => ['required', 'array:jornales,dias,semanas'],
            'duracion.*.jornales' => ['present', 'nullable', 'numeric', 'decimal:0,2', 'between:0,999999'],
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
                    $duracion[$escenario][$campo] = $campo === 'jornales' ? (float) $valor : (int) $valor;
                }
            }
        }
        $presupuesto->duracion_apupi = $duracion ?: null;
        $presupuesto->save();

        return $this->volverAlDetalle($request, $presupuesto, 'Duración de obra actualizada.');
    }

    public function updateCostoApupi(Request $request, Presupuesto $presupuesto, string $detalle)
    {
        $datos = $request->validateWithBag('costoApupi', [
            'cantidad' => ['required_without:precio_unitario', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
            'precio_unitario' => ['required_without:cantidad', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
        ], [], ['precio_unitario' => 'costo unitario']);

        DB::transaction(function () use ($presupuesto, $detalle, $datos) {
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            $vol = $actual->calcularVolumetria();
            $duracion = $actual->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
            $propuesta = $this->filasPropuesta($actual, $vol, $duracion);
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
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
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
        $datos = $request->validate([
            'cantidad' => ['required_without:precio_unitario', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
            'precio_unitario' => ['required_without:cantidad', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
        ], [], ['precio_unitario' => 'precio unitario de venta']);

        DB::transaction(function () use ($presupuesto, $renglon, $datos) {
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            $vol = $actual->calcularVolumetria();
            if ($renglon === 'venta-215') {
                $duracion = $actual->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
                $filas = $this->filasPropuesta($actual, $vol, $duracion);
            } else {
                $filas = PropuestaApupi::filas($actual, $vol['porDiametro']);
            }
            $fila = $filas->get($renglon);
            abort_if($fila === null, 404);
            $ajustes = $actual->ajustes_propuesta_apupi ?? [];
            $anterior = $fila['clave_anterior'];
            $cambios = $ajustes[$renglon] ?? $ajustes[$anterior ?? ''] ?? [];
            if ($anterior !== null) {
                unset($ajustes[$anterior]);
            }
            foreach ($datos as $campo => $valor) {
                if ((float) $valor === (float) $fila['base'][$campo]) {
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

    private function filasPropuesta(Presupuesto $presupuesto, array $vol, array $duracion): Collection
    {
        // La venta usa el mínimo de traslado y los precios capturados de fianza y financiamiento.
        $resumen = $presupuesto->resumenApupi($presupuesto->filasApupi($duracion), $vol['totales']['perforacion']);

        return PropuestaApupi::filas($presupuesto, $vol['porDiametro'], $resumen['realista']['minimos'][PresupuestoDetalle::PARTIDA_TRASLADO]);
    }

    private function calcularApupi(Presupuesto $presupuesto, array $vol, array $duracion): array
    {
        $filas = $this->filasPropuesta($presupuesto, $vol, $duracion);
        $costos = $presupuesto->filasApupi($duracion, PropuestaApupi::totalVenta($filas));
        $resumen = $presupuesto->resumenApupi($costos, $vol['totales']['perforacion']);
        $resumen['propuesta'] = PropuestaApupi::calcular($filas, $vol, $resumen, $costos, $duracion);

        return [$costos, $resumen];
    }

    private function respuestaCostoApupi(Request $request, Presupuesto $presupuesto)
    {
        if ($request->expectsJson()) {
            $presupuesto->refresh();
            $vol = $presupuesto->calcularVolumetria();
            $duracion = $presupuesto->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);

            [$costos, $resumen] = $this->calcularApupi($presupuesto, $vol, $duracion);
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

    private function guardarDatos(Request $request, Presupuesto $presupuesto): void
    {
        $importado = $presupuesto->esImportado();
        $folioUnico = Rule::unique('presupuestos', 'codigo_proyecto');
        if ($presupuesto->exists) {
            $folioUnico->ignore($presupuesto);
        }
        $datos = $request->validateWithBag('datosPresupuesto', [
            'codigo_proyecto' => $importado ? ['prohibited'] : ['required', 'string', 'max:255', $folioUnico],
            'cliente_id' => [$importado ? 'nullable' : 'required', 'integer', Rule::exists('clientes', 'id')],
            'nombre_obra' => [$importado ? 'nullable' : 'required', 'string', 'max:255'],
            'equipo_propuesto' => ['nullable', Rule::in(array_keys(Presupuesto::EQUIPOS_PROPUESTOS))],
        ], [], ['codigo_proyecto' => 'folio', 'cliente_id' => 'cliente', 'nombre_obra' => 'nombre de obra', 'equipo_propuesto' => 'equipo propuesto']);

        if ($importado) {
            unset($datos['codigo_proyecto']);
        } else {
            $datos['nombre_cliente'] = Cliente::findOrFail($datos['cliente_id'])->nombre_comercial;
        }

        try {
            $presupuesto->fill($datos)->save();
        } catch (UniqueConstraintViolationException $e) {
            if ($importado) {
                throw $e;
            }

            throw ValidationException::withMessages(['codigo_proyecto' => 'Este folio ya está en uso.'])
                ->errorBag('datosPresupuesto');
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
        $volumetria = $presupuesto->calcularVolumetria();
        $duracionApupi = $presupuesto->duracionApupi($volumetria['filas']->isEmpty() ? null : $volumetria['totales']['jornales']);
        $editarVol = $volumetria['filas']->firstWhere('pila.id', $request->integer('editar_pila'))['pila'] ?? null;

        $clienteSeleccionado = $this->clienteSeleccionado($request, $presupuesto);
        [$filasApupi, $resumenApupi] = $this->calcularApupi($presupuesto, $volumetria, $duracionApupi);
        $relacionGastos = $this->relacionGastos($presupuesto, $filasApupi, $duracionApupi);
        $presentacion = PresentacionPresupuesto::preparar($presupuesto, $filasApupi, $resumenApupi, $duracionApupi);
        $previo = PrevioPresupuesto::preparar($presupuesto, $filasApupi, $resumenApupi['propuesta'], $duracionApupi);
        $versionGuardada = $presupuesto->versiones()->orderByDesc('revision')->first(['id', 'revision']);

        return view('presupuesto.show', compact('presupuesto', 'relacionGastos', 'volumetria', 'editarVol', 'clienteSeleccionado', 'duracionApupi', 'filasApupi', 'resumenApupi', 'versionGuardada', 'presentacion', 'previo'));
    }

    public function storeVersion(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('versionPresupuesto', ['revision' => ['sometimes', 'required', 'integer', 'min:0']]);
        $revision = (int) ($datos['revision'] ?? 0);
        $version = DB::transaction(function () use ($request, $presupuesto, $revision): PresupuestoVersion {
            if ($revision === 0 && $presupuesto->consecutivo_cliente === null) {
                try {
                    (clone $presupuesto)->asignarConsecutivoCliente();
                } catch (ValidationException $e) {
                    throw $e->errorBag('versionPresupuesto');
                }
            }

            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            $guardada = $actual->versiones()->where('revision', $revision)->lockForUpdate()->first(['id']);
            if ($guardada !== null) {
                return $guardada;
            }
            $origen = $actual->version_origen_borrador_id;
            if ($revision > 0 && ($origen === null || $revision !== (int) $actual->versiones()->max('revision') + 1)) {
                throw ValidationException::withMessages([
                    'revision' => 'El borrador de esta revisión ya no está disponible. Abre el historial para ver su estado.',
                ])->errorBag('versionPresupuesto');
            }

            $actual->load(['cliente', 'detalles', 'pilas', 'resumenes']);
            $vol = $actual->calcularVolumetria();
            $duracion = $actual->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
            [$costos, $resumen] = $this->calcularApupi($actual, $vol, $duracion);
            $autor = $request->user()->getKey();
            $version = $actual->versiones()->create([
                'revision' => $revision,
                'version_origen_id' => $origen,
                'motivo' => $actual->motivo_borrador_revision,
                'creado_por' => $autor,
                'datos' => DatosPresupuestoVersion::capturar($actual, $vol, $duracion, $costos, $resumen, $this->gastosBase($actual)),
                'total_venta' => $resumen['propuesta']['total'],
            ]);
            $version->eventos()->create(['tipo' => PresupuestoEvento::GUARDADO, 'registrado_por' => $autor]);
            if ($origen !== null) {
                $actual->forceFill(['version_origen_borrador_id' => null, 'motivo_borrador_revision' => null])->save();
            }

            return $version;
        }, 3);

        return redirect()->route('presupuesto.versiones.show', [$presupuesto, $version]);
    }

    public function historialVersiones(Presupuesto $presupuesto)
    {
        $versiones = $presupuesto->versiones()->with(['autor:id,name', 'ultimoEvento'])->orderByDesc('revision')
            ->get(['id', 'presupuesto_id', 'revision', 'creado_por', 'motivo', 'total_venta', 'created_at']);
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
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
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

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
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

        return view('presupuesto.version', compact('presupuesto', 'version', 'obrasPlaneacion'));
    }

    public function aplicarPlaneacionSemanal(Request $request, Presupuesto $presupuesto, int $version)
    {
        abort_unless($request->user()->hasAnyRole(PrevioPresupuesto::ADMINISTRADORES), 403);
        $datos = $request->validateWithBag('planeacionSemanal', ['obra_id' => ['required', 'integer']]);
        $guardada = $presupuesto->versiones()->findOrFail($version);
        $relacion = $guardada->datos['relacion_gastos'] ?? [];
        abort_unless($relacion['planeacion']['publicable'] ?? false, 422, 'La versión requiere una programación semanal completa.');
        $filas = collect($relacion['partidas'])->flatMap(fn (array $grupo) => collect($grupo['filas'])
            ->map(fn (array $fila) => $fila + ['partida' => $grupo['partida']]))
            ->filter(fn (array $fila) => $fila['monto_programado'] != 0);
        $error = fn (string $mensaje) => ValidationException::withMessages(['obra_id' => $mensaje])->errorBag('planeacionSemanal');

        if ($filas->contains(fn (array $fila) => $fila['monto_programado'] < 0
            || max($fila['monto_programado'], $fila['cantidad'], $fila['precio_unitario']) > 9999999999999.99)) {
            throw $error('La planeación de Obra requiere importes positivos y valores dentro del rango de captura.');
        }

        $obra = DB::transaction(function () use ($presupuesto, $guardada, $datos, $filas, $error): Obra {
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

            foreach ($filas as $fila) {
                $gasto = $obra->planeacionGastos()->create([
                    'partida' => $fila['partida'],
                    'concepto' => $fila['concepto'],
                    'unidad' => $fila['unidad'],
                    'cantidad' => $fila['cantidad'] ?? 0,
                    'precio_unitario' => $fila['precio_unitario'],
                    'monto_programado' => $fila['monto_programado'],
                    'numero_semana' => 0,
                ]);
                $semanas = [];
                foreach ($fila['planeacion']['montos'] as $semana => $centavos) {
                    $semanas[] = ['numero_semana' => $semana, 'monto_programado' => $centavos / 100];
                }
                $gasto->semanas()->createMany($semanas);
            }
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
            $guardada = $presupuesto->versiones()->lockForUpdate()->findOrFail($version, ['id']);
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
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            $vol = $actual->calcularVolumetria();
            $duracion = $actual->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
            [$costos] = $this->calcularApupi($actual, $vol, $duracion);
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
            Presupuesto::lockForUpdate()->findOrFail($presupuesto->id)->pilasVol()->create($datos);
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoVol', 'Tipo de pila agregado.');
    }

    public function updatePilaVol(Request $request, Presupuesto $presupuesto, int $pilaVol)
    {
        DB::transaction(function () use ($request, $presupuesto, $pilaVol) {
            $pila = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id)->pilasVol()->findOrFail($pilaVol);
            $pila->update($this->validarPilaVol($request, anterior: $pila));
        }, 3);

        return redirect()->to(route('presupuesto.show', [$presupuesto, 'tab' => 'pilas', 'editar_pila' => $pilaVol]) . '#captura-vol')
            ->with('estadoVol', 'Tipo de pila actualizado.');
    }

    public function updateTraslapeVol(Request $request, Presupuesto $presupuesto)
    {
        $datos = $request->validateWithBag('traslapeVol', [
            'vol_traslape_varilla' => ['required', 'numeric', 'decimal:0,5', 'between:0,99999.99999'],
        ]);
        $presupuesto->vol_traslape_varilla = $datos['vol_traslape_varilla'];
        $presupuesto->save();

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

    private function validarPilaVol(Request $request, bool $permitirIncompleto = false, ?PresupuestoVolPila $anterior = null): array
    {
        $captura = $permitirIncompleto ? 'nullable' : 'required';
        $datos = $request->validateWithBag('pilaVol', [
            'tipo' => [$captura, 'string', 'max:80'],
            'diametro' => [$captura, 'numeric', 'decimal:0,3', 'between:0.15,99999.999'],
            'piezas' => [$captura, 'integer', 'between:1,4294967295'],
            'longitud_l0' => [$captura, 'numeric', 'decimal:0,3', 'between:0.001,99999.999'],
            'avance_metros_por_jornal' => ['sometimes', $captura, 'numeric', 'decimal:0,5', 'between:0.00001,999999999.99999'],
            'longitud_l1' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'longitud_l2' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'intervalo_l0' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'intervalo_l1' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'intervalo_l2' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,99999.999'],
            'varillas_l0' => ['nullable', 'integer', 'between:0,4294967295'],
            'varillas_l1' => ['nullable', 'integer', 'between:0,4294967295'],
            'calibre_varilla_l0' => ['nullable', Rule::in(array_keys(PresupuestoVolPila::PESOS_VARILLA))],
            'calibre_varilla_l1' => ['nullable', Rule::in(array_keys(PresupuestoVolPila::PESOS_VARILLA))],
            'acero_kg_por_pila' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,9999999.999'],
        ], [], [
            'longitud_l0' => 'longitud L0',
            'avance_metros_por_jornal' => 'avance en metros por jornal',
            'calibre_varilla_l0' => 'calibre de varilla L0',
            'calibre_varilla_l1' => 'calibre de varilla L1',
        ]);

        foreach (['longitud_l1', 'longitud_l2', 'intervalo_l0', 'intervalo_l1', 'intervalo_l2', 'varillas_l0', 'varillas_l1'] as $campo) {
            $datos[$campo] ??= 0;
        }
        $datos['avance_metros_por_jornal'] ??= $anterior
            ? max(0.00001, round($anterior->volumetria()['avance_metros_por_jornal'], 5))
            : PresupuestoVolPila::AVANCE_PREDETERMINADO;
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
            Presupuesto::lockForUpdate()->findOrFail($presupuesto->id)->pilasVol()->findOrFail($pilaVol)->delete();
        }, 3);

        return redirect()->route('presupuesto.show', [$presupuesto, 'tab' => 'pilas'])
            ->with('estadoVol', 'Tipo de pila eliminado.');
    }
    public function exportVersionPdf(Presupuesto $presupuesto, int $version)
    {
        $version = $presupuesto->versiones()->findOrFail($version);
        $version->setRelation('presupuesto', $presupuesto);
        $datos = $version->datos;
        $origen = $datos['presupuesto'];
        $folio = $version->folio();
        $cabecera = [
            'cliente' => ($datos['cliente']['nombre_comercial'] ?? null) ?: $origen['nombre_cliente'],
            'obra' => $origen['nombre_obra'],
            'equipo' => $origen['equipo'],
            'folio' => $folio,
            'referencia' => $origen['codigo_proyecto'],
            'fecha' => $version->created_at->format('d/m/Y'),
        ];
        $archivo = $this->nombrePdf($origen['codigo_proyecto'], ltrim($folio, '#'));

        if (isset($datos['presentacion'])) {
            return $this->imprimirPresentacion($cabecera, $datos['presentacion'], $archivo);
        }

        $tablaConsolidada = collect($datos['propuesta']['filas'])->where('cantidad', '>', 0);
        $totalGeneral = $version->total_venta;
        $esVersion = true;

        return Pdf::loadView('presupuesto.pdf_template', compact('cabecera', 'tablaConsolidada', 'totalGeneral', 'esVersion'))
            ->stream($archivo);
    }

    public function exportPdf($id)
    {
        $presupuesto = Presupuesto::with(['detalles', 'resumenes', 'pilas', 'cliente'])->findOrFail($id);
        $vol = $presupuesto->calcularVolumetria();
        $duracion = $presupuesto->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales']);
        [$costos, $resumen] = $this->calcularApupi($presupuesto, $vol, $duracion);
        $presentacion = PresentacionPresupuesto::preparar($presupuesto, $costos, $resumen, $duracion);
        $cabecera = [
            'cliente' => $presupuesto->nombreClienteVisible(),
            'obra' => $presupuesto->nombre_obra,
            'equipo' => Presupuesto::EQUIPOS_PROPUESTOS[$presupuesto->equipo_propuesto] ?? null,
            'folio' => $presupuesto->codigo_proyecto,
            'referencia' => null,
            'fecha' => now()->format('d/m/Y'),
        ];

        return $this->imprimirPresentacion($cabecera, $presentacion, $this->nombrePdf($presupuesto->codigo_proyecto));
    }

    private function imprimirPresentacion(array $cabecera, array $presentacion, string $archivo)
    {
        $presentacion['filas'] = PresentacionPresupuesto::numerar(collect($presentacion['filas']))->all();
        $pdf = Pdf::loadView('presupuesto.pdf-comercial', compact('cabecera', 'presentacion'))->setPaper('letter');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $lienzo = $dompdf->getCanvas();
        $lienzo->page_text(
            $lienzo->get_width() / 2 - 23,
            $lienzo->get_height() - 22,
            'Hoja {PAGE_NUM} de {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'),
            7
        );

        return $pdf->stream($archivo);
    }

    private function nombrePdf(string $referencia, ?string $revision = null): string
    {
        $referencia = substr(preg_replace('/[^A-Za-z0-9_-]+/', '_', $referencia), 0, 90);
        $sufijo = $revision === null ? '' : "_{$revision}";

        return "Presupuesto_{$referencia}{$sufijo}.pdf";
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $presupuesto = Presupuesto::lockForUpdate()->findOrFail($id);
            if ($presupuesto->versiones()->exists()) {
                DB::rollBack();

                return redirect()->route('presupuesto.index')
                    ->with('error', 'El presupuesto tiene versiones guardadas y no se puede eliminar.');
            }

            // Borrar tablas hijas
            PresupuestoDetalle::where('presupuesto_id', $presupuesto->id)->delete();
            PresupuestoPila::where('presupuesto_id', $presupuesto->id)->delete();
            PresupuestoResumen::where('presupuesto_id', $presupuesto->id)->delete();

            // Borrar cabecera
            $presupuesto->delete();

            DB::commit();

            return redirect()
                ->route('presupuesto.index')
                ->with('success', 'Presupuesto eliminado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('presupuesto.index')
                ->with('error', 'Ocurrió un error al eliminar el presupuesto.');
        }
    }
}
