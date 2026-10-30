<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Services\Mantenimiento\MantenimientoAbastecimientoService;
use App\Services\Mantenimiento\MantenimientoBorradorService;
use App\Services\Mantenimiento\MantenimientoEvidenciaService;
use App\Services\Mantenimiento\MantenimientoMaterialesService;
use App\Services\Mantenimiento\MantenimientoOrdenService;
use App\Services\Mobile\AppMobileAccessService;
use App\Services\Mobile\ServicioProgramadoConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ServicioProgramadoController extends Controller
{
    private const CAMPOS = ['maquina_id', 'vehiculo_id', 'obra_id', 'categoria_mantenimiento',
        'servicio_preventivo_tipo_id', 'descripcion', 'km_actuales', 'km_proximo_servicio', 'horometro', 'fecha_programada'];

    public function __construct(private MantenimientoBorradorService $borradores, private AppMobileAccessService $access) {}

    public function index(Request $request, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'estatus' => ['nullable', Rule::in(ServicioProgramadoConsultaService::ESTADOS)],
            'equipo_tipo' => ['nullable', Rule::in(['maquinas', 'vehiculos'])],
            'maquina_id' => ['nullable', 'integer', 'min:1', Rule::prohibitedIf($request->filled('vehiculo_id') || $request->input('equipo_tipo') === 'vehiculos')],
            'vehiculo_id' => ['nullable', 'integer', 'min:1', Rule::prohibitedIf($request->filled('maquina_id') || $request->input('equipo_tipo') === 'maquinas')],
            'obra_id' => ['nullable', 'integer', 'min:1'],
            'solo_mios' => ['nullable', 'boolean'],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('fecha_desde'), 'after_or_equal:fecha_desde')],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $empleadoId = $this->empleadoConsulta($request);
        $query = $consulta->consulta();
        if (! empty($filtros['equipo_tipo'])) {
            $esMaquina = $filtros['equipo_tipo'] === 'maquinas';
            $query->whereNotNull($esMaquina ? 'maquina_id' : 'vehiculo_id')
                ->whereNull($esMaquina ? 'vehiculo_id' : 'maquina_id');
        }
        foreach (['estatus', 'maquina_id', 'vehiculo_id', 'obra_id'] as $campo) {
            if (isset($filtros[$campo])) {
                $query->where($campo, $filtros[$campo]);
            }
        }
        if ($request->boolean('solo_mios')) {
            $empleadoId === null ? $query->whereRaw('1 = 0') : $query->where('mecanico_id', $empleadoId);
        }
        foreach (['fecha_desde' => '>=', 'fecha_hasta' => '<='] as $campo => $operador) {
            if (isset($filtros[$campo])) {
                $query->whereDate('fecha_programada', $operador, $filtros[$campo]);
            }
        }
        if (isset($filtros['q']) && trim($filtros['q']) !== '') {
            $q = '%'.trim($filtros['q']).'%';
            $query->where(function ($query) use ($q) {
                $query->where('descripcion', 'like', $q)->orWhere('categoria_mantenimiento', 'like', $q)
                    ->orWhere('orden_servicio_folio', 'like', $q)
                    ->orWhereHas('maquina', fn ($m) => $m->where('nombre', 'like', $q)->orWhere('codigo', 'like', $q))
                    ->orWhereHas('vehiculo', fn ($v) => $v->where('marca', 'like', $q)->orWhere('modelo', 'like', $q)->orWhere('placas', 'like', $q));
            });
        }
        $pagina = $query->orderByDesc('id')->paginate($filtros['per_page'] ?? 25);

        return response()->json(['ok' => true,
            'data' => $pagina->getCollection()->map(fn ($s) => $consulta->resumen($s, $empleadoId))->values(),
            'meta' => ['current_page' => $pagina->currentPage(), 'per_page' => $pagina->perPage(),
                'total' => $pagina->total(), 'last_page' => $pagina->lastPage()],
        ]);
    }

    public function show(Request $request, string $servicio, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        // El mismo alcance se aplica por ID; emergencias quedan fuera de esta API.
        $registro = $consulta->consulta()->findOrFail($servicio);

        return response()->json(['ok' => true, 'data' => $consulta->detalle($registro, $this->empleadoConsulta($request))]);
    }

    private function empleadoConsulta(Request $request): ?int
    {
        $usuario = $this->access->usuarioActivo($request->user());

        return $usuario->empleado_id && $usuario->empleado()->exists() ? (int) $usuario->empleado_id : null;
    }

    public function generarOrden(Request $request, Mantenimiento $servicio, MantenimientoOrdenService $ordenes, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        if ($request->all() !== []) {
            throw ValidationException::withMessages(['payload' => 'Esta acción no recibe campos: usa el borrador guardado para generar la orden.']);
        }
        $empleadoId = $this->empleadoId($request);
        try {
            $actual = $ordenes->generar($servicio, (int) $request->user()->getAuthIdentifier(), $empleadoId);
        } catch (ConflictHttpException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'data' => $consulta->detalle($consulta->consulta()->findOrFail($actual->id), $empleadoId)]);
    }

    public function materiales(Request $request, Mantenimiento $servicio, MantenimientoMaterialesService $materiales, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        $empleadoId = $this->empleadoId($request);
        try {
            $actual = $materiales->actualizar($servicio, $request->all(), $empleadoId);
        } catch (ConflictHttpException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'data' => $consulta->detalle($consulta->consulta()->findOrFail($actual->id), $empleadoId)]);
    }

    public function abastecimiento(Request $request, Mantenimiento $servicio, MantenimientoAbastecimientoService $abastecimiento, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        if (array_diff(array_keys($request->all()), ['tipo'])) {
            throw ValidationException::withMessages(['payload' => 'Esta acción solo recibe el tipo de solicitud; materiales y cantidades se resuelven en servidor.']);
        }
        $datos = $request->validate(['tipo' => ['required', 'in:almacen,compra']]);
        $empleadoId = $this->empleadoId($request);
        try {
            $solicitud = $abastecimiento->crearSolicitud($servicio, $datos['tipo'], (int) $request->user()->getAuthIdentifier(), $empleadoId);
        } catch (ConflictHttpException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 409);
        }

        return response()->json(['ok' => true,
            'data' => $consulta->detalle($consulta->consulta()->findOrFail($servicio->id), $empleadoId),
            'solicitud_id' => (int) $solicitud->id,
        ]);
    }

    public function iniciar(Request $request, Mantenimiento $servicio, \App\Services\Mantenimiento\MantenimientoEjecucionService $ejecucion, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        return $this->ejecutar($request, $servicio, 'iniciar', $ejecucion, $consulta);
    }

    public function completar(Request $request, Mantenimiento $servicio, \App\Services\Mantenimiento\MantenimientoEjecucionService $ejecucion, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        return $this->ejecutar($request, $servicio, 'completar', $ejecucion, $consulta);
    }

    private function ejecutar(Request $request, Mantenimiento $servicio, string $accion, \App\Services\Mantenimiento\MantenimientoEjecucionService $ejecucion, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        $empleadoId = $this->empleadoId($request);
        try {
            $actual = $ejecucion->ejecutar($servicio, $accion, $request->all(), $empleadoId);
        } catch (ConflictHttpException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'data' => $consulta->detalle($consulta->consulta()->findOrFail($actual->id), $empleadoId)]);
    }

    public function evidencias(Request $request, Mantenimiento $servicio, MantenimientoEvidenciaService $evidencias, ServicioProgramadoConsultaService $consulta): JsonResponse
    {
        $empleadoId = $this->empleadoId($request);
        $actual = $evidencias->agregar($servicio, $request->all(), $empleadoId);

        return response()->json(['ok' => true, 'data' => $consulta->detalle($consulta->consulta()->findOrFail($actual->id), $empleadoId)], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $this->validarCamposProtegidos($request);
        $empleadoId = $this->empleadoId($request);
        $servicio = $this->borradores->crear(array_merge($request->only(self::CAMPOS), [
            'tipo' => 'programado', 'mecanico_id' => $empleadoId,
        ]));

        return $this->respuesta($servicio, 201);
    }

    public function update(Request $request, Mantenimiento $servicio): JsonResponse
    {
        $this->validarCamposProtegidos($request);
        try {
            $servicio = $this->borradores->actualizar($servicio, $request->only(self::CAMPOS), $this->empleadoId($request));
        } catch (ConflictHttpException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 409);
        }

        return $this->respuesta($servicio);
    }

    private function empleadoId(Request $request): int
    {
        $usuarioApp = $this->access->usuarioActivo($request->user());
        if (! $usuarioApp->empleado_id || ! $usuarioApp->empleado()->exists()) {
            throw ValidationException::withMessages(['mecanico_id' => 'Tu usuario app debe tener un empleado vinculado para modificar servicios.']);
        }

        return (int) $usuarioApp->empleado_id;
    }

    private function validarCamposProtegidos(Request $request): void
    {
        $rules = ['tipo' => ['sometimes', 'in:programado']];
        foreach (['mecanico_id', 'estatus', 'abastecimiento_tipo', 'abastecimiento_estado',
            'orden_servicio_folio', 'orden_servicio_generada_at', 'orden_servicio_generada_por',
            'costo_mano_obra', 'costo_refacciones', 'costo_total', 'detalles',
            'fecha_inicio', 'fecha_fin', 'fotos', 'foto_descripciones'] as $campo) {
            $rules[$campo] = ['prohibited'];
        }
        $request->validate($rules);
    }

    private function respuesta(Mantenimiento $servicio, int $status = 200): JsonResponse
    {
        $data = $servicio->only(array_merge(self::CAMPOS, [
            'id', 'tipo', 'mecanico_id', 'estatus', 'abastecimiento_estado', 'orden_servicio_folio',
            'costo_mano_obra', 'costo_refacciones', 'costo_total', 'created_at', 'updated_at',
        ]));
        foreach (['id', 'maquina_id', 'vehiculo_id', 'obra_id', 'mecanico_id', 'servicio_preventivo_tipo_id', 'km_actuales', 'km_proximo_servicio'] as $campo) {
            $data[$campo] = $data[$campo] !== null ? (int) $data[$campo] : null;
        }
        foreach (['horometro', 'costo_mano_obra', 'costo_refacciones', 'costo_total'] as $campo) {
            $data[$campo] = $data[$campo] !== null ? (float) $data[$campo] : null;
        }

        return response()->json(['ok' => true, 'data' => $data], $status);
    }
}
