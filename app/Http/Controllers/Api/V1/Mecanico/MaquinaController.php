<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Services\Maquinas\MaquinaAsignacionService;
use App\Services\Maquinas\MaquinaHorasService;
use App\Services\Maquinas\MaquinaUbicacionService;
use App\Services\Mobile\Equipos\EquipoMecanicoPresenter;
use App\Services\Mobile\Equipos\MaquinasConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MaquinaController extends Controller
{
    public function index(Request $request, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->listado($consulta->index($request), 'maquina'));
    }

    public function show(Request $request, Maquina $maquina, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->detalle($consulta->show($request, $maquina), 'maquina'));
    }

    public function opciones(Request $request, string $maquinaId, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter, MaquinaAsignacionService $asignaciones): JsonResponse
    {
        // Resolver el ID después del permiso específico, sin binding implícito.
        if ($request->query('uso') === 'ajuste') {
            abort_unless($horas->puedeCapturar($request->user()), 403);
        } elseif ($request->query('uso') === 'asignacion') {
            abort_unless($asignaciones->puedeAsignar($request->user()), 403);
        }
        try {
            $filtros = $request->validate([
                'uso' => ['nullable', Rule::in(['ajuste', 'asignacion'])], 'q' => ['nullable', 'string', 'max:120'],
                'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,50'],
            ]);
        } catch (ValidationException $error) {
            return response()->json(['ok' => false, 'message' => $error->getMessage(), 'errors' => $error->errors()], 422);
        }
        $maquina = Maquina::findOrFail($maquinaId);
        $data = $this->datosOperacion($request, $maquina, $horas, $consulta, $presenter);
        $payload = ['ok' => true, 'data' => $data];
        if (isset($filtros['uso'])) {
            $query = Obra::query()->select('id', 'nombre', 'clave_obra')
                ->where('estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)->orderBy('nombre')->orderBy('id');
            if ($filtros['uso'] === 'asignacion') {
                $query->where('estatus_nuevo', '!=', Obra::ESTATUS_TERMINADA)
                    ->whereHas('pilas', fn ($q) => $q->where('activo', true));
            }
            if (! empty($filtros['q'])) {
                $term = trim($filtros['q']);
                $query->where(fn ($q) => $q->where('nombre', 'like', "%{$term}%")->orWhere('clave_obra', 'like', "%{$term}%"));
            }
            $rows = $query->paginate($filtros['per_page'] ?? 20);
            $payload['data']['obras'] = $rows->getCollection()->map->only(['id', 'nombre', 'clave_obra'])->values();
            $payload['meta'] = ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(), 'total' => $rows->total()];
        }

        return response()->json($payload);
    }

    public function horas(Request $request, string $maquinaId, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        try {
            $operacion = $horas->guardarMovil((int) $maquinaId, $request->all(), $request->user());
        } catch (ConflictHttpException $error) {
            return response()->json(['ok' => false, 'code' => 'contexto_maquina_cambio', 'message' => $error->getMessage()], 409);
        } catch (ValidationException $error) {
            return response()->json(['ok' => false, 'message' => $error->getMessage(), 'errors' => $error->errors()], 422);
        }
        $data = $this->datosOperacion($request, Maquina::findOrFail($maquinaId), $horas, $consulta, $presenter);
        $data['operacion'] = $operacion;

        return response()->json(['ok' => true, 'message' => $operacion['tipo'] === 'registro_horas'
            ? 'Horas registradas correctamente.' : 'Horómetro ajustado correctamente.', 'data' => $data], $operacion['reutilizada'] ? 200 : 201);
    }

    public function asignarObra(Request $request, string $maquinaId, MaquinaAsignacionService $asignaciones, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return $this->cambiarAsignacion($request, $maquinaId, $asignaciones, $horas, $consulta, $presenter, true);
    }

    public function bajaObra(Request $request, string $maquinaId, MaquinaAsignacionService $asignaciones, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return $this->cambiarAsignacion($request, $maquinaId, $asignaciones, $horas, $consulta, $presenter, false);
    }

    private function cambiarAsignacion(Request $request, string $maquinaId, MaquinaAsignacionService $asignaciones, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter, bool $asignar): JsonResponse
    {
        try {
            $operacion = $asignar
                ? $asignaciones->asignar((int) $maquinaId, $request->all(), $request->user())
                : $asignaciones->retirar((int) $maquinaId, $request->all(), $request->user());
        } catch (ConflictHttpException $error) {
            return response()->json(['ok' => false, 'code' => 'contexto_maquina_cambio', 'message' => $error->getMessage()], 409);
        } catch (ValidationException $error) {
            return response()->json(['ok' => false, 'message' => $error->getMessage(), 'errors' => $error->errors()], 422);
        }
        $data = $this->datosOperacion($request, Maquina::findOrFail($maquinaId), $horas, $consulta, $presenter);
        $data['operacion'] = $operacion;

        return response()->json(['ok' => true, 'message' => $asignar ? 'Máquina asignada correctamente.' : 'Máquina retirada de la obra y enviada al patio.',
            'data' => $data], $asignar && ! $operacion['reutilizada'] ? 201 : 200);
    }

    public function ubicacion(Request $request, string $maquinaId, MaquinaUbicacionService $ubicaciones, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        try {
            $operacion = $ubicaciones->guardar((int) $maquinaId, $request->all(), $request->user());
        } catch (ConflictHttpException $error) {
            return response()->json(['ok' => false, 'code' => 'contexto_maquina_cambio', 'message' => $error->getMessage()], 409);
        } catch (ValidationException $error) {
            return response()->json(['ok' => false, 'message' => $error->getMessage(), 'errors' => $error->errors()], 422);
        }
        $data = $this->datosOperacion($request, Maquina::findOrFail($maquinaId), $horas, $consulta, $presenter);
        $data['operacion'] = $operacion;

        return response()->json(['ok' => true, 'message' => 'Ubicación actualizada correctamente.', 'data' => $data]);
    }

    private function datosOperacion(Request $request, Maquina $maquina, MaquinaHorasService $horas, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): array
    {
        $ficha = $presenter->detalle($consulta->show($request, $maquina), 'maquina')['data'];
        $asignacion = $ficha['asignacion_activa'];
        $retiro = $ficha['asignacion_retiro'];
        $puedeHoras = $horas->puedeCapturar($request->user());
        $asignaciones = app(MaquinaAsignacionService::class);
        $tieneActiva = ObraMaquina::query()->where('maquina_id', $maquina->id)->where('estado', 'activa')->whereNull('fecha_fin')->exists();
        $ubicacion = app(MaquinaUbicacionService::class)->datos($maquina, $request->user());

        return [
            'maquina' => $ficha['maquina'], 'asignacion_activa' => $asignacion, 'asignacion_retiro' => $retiro,
            'contexto_retiro' => ['asignacion_esperada_id' => $retiro['obra_maquina_id'] ?? null,
                'horometro_esperado' => $ficha['maquina']['horometro_actual']],
            'contexto' => ['asignacion_esperada_id' => $asignacion['obra_maquina_id'] ?? null,
                'horometro_esperado' => $ficha['maquina']['horometro_actual']],
            'contexto_ubicacion' => $ubicacion['contexto_ubicacion'],
            'ubicaciones_permitidas' => $ubicacion['ubicaciones_permitidas'],
            'acciones_permitidas' => ['registrar_horas' => $puedeHoras && $asignacion !== null,
                'ajustar_horometro' => $puedeHoras && $asignacion === null,
                'asignar_obra' => $asignaciones->puedeAsignar($request->user()) && $maquina->estado === Maquina::ESTADO_OPERATIVA && ! $tieneActiva,
                'retirar_obra' => $asignaciones->puedeRetirar($request->user()) && $retiro !== null,
                'cambiar_ubicacion' => $ubicacion['cambiar_ubicacion']],
        ];
    }
}
