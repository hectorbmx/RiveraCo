<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\VehiculoEmpleado;
use App\Models\VehiculoEmpleadoKmLog;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehiculoKmController extends Controller
{
    public function __construct(private AppMobileContextService $contextService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user()->load('usuarioApp');
        $empleadoId = $user->usuarioApp?->empleado_id;

        if (!$empleadoId && !$this->contextService->puedeVerTodasLasObrasResidente($user)) {
            return response()->json(['ok' => false, 'message' => 'No hay empleado asociado a este usuario.'], 422);
        }

        $obraActiva = $this->obraActivaParaUsuario($request);
        $asignacion = $this->vehiculoActivoParaContexto($request, $empleadoId ? (int) $empleadoId : null, $obraActiva);

        if (!$asignacion) {
            return response()->json(['ok' => false, 'message' => 'No tienes un vehiculo asignado actualmente.'], 404);
        }

        if (!$obraActiva) {
            return response()->json([
                'ok' => true,
                'vehiculo_empleado_id' => $asignacion->id,
                'asignacion' => $this->formatearAsignacion($asignacion),
                'obra_actual' => null,
                'message' => 'No tienes una obra activa asignada para consultar registros del vehiculo.',
                'data' => [],
            ]);
        }

        $logs = VehiculoEmpleadoKmLog::where('vehiculo_empleado_id', $asignacion->id)
            ->where('obra_id', $obraActiva->id)
            ->orderByDesc('fecha')
            ->limit(50)
            ->get()
            ->map(fn ($log) => $this->formatearLog($log));

        return response()->json([
            'ok' => true,
            'vehiculo_empleado_id' => $asignacion->id,
            'asignacion' => $this->formatearAsignacion($asignacion),
            'obra_actual' => [
                'id' => $obraActiva->id,
                'nombre' => $obraActiva->nombre,
                'clave_obra' => $obraActiva->clave_obra,
            ],
            'data' => $logs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'km' => ['required', 'integer', 'min:0'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'foto_ticket_gasolina' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'monto_gasolina' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notas' => ['nullable', 'string', 'max:500'],
            'obra_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user()->load('usuarioApp');
        $empleadoId = $user->usuarioApp?->empleado_id;

        if (!$empleadoId && !$this->contextService->puedeVerTodasLasObrasResidente($user)) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay empleado asociado a este usuario.',
            ], 422);
        }

        $obraActiva = $this->obraActivaParaUsuario($request);
        $asignacion = $this->vehiculoActivoParaContexto($request, $empleadoId ? (int) $empleadoId : null, $obraActiva);

        if (!$asignacion) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes un vehiculo asignado actualmente.',
            ], 404);
        }

        if (!$obraActiva) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes una obra activa asignada para registrar kilometraje del vehiculo.',
            ], 422);
        }

        $ultimoKmLog = VehiculoEmpleadoKmLog::where('vehiculo_empleado_id', $asignacion->id)->max('km');

        $minPermitido = max(
            (int) ($asignacion->km_inicial ?? 0),
            (int) ($ultimoKmLog ?? 0)
        );

        if ((int) $data['km'] < $minPermitido) {
            return response()->json([
                'ok' => false,
                'message' => "El kilometraje no puede ser menor a {$minPermitido}.",
            ], 422);
        }

        return DB::transaction(function () use ($data, $asignacion, $obraActiva, $request) {
            $path = $data['foto']->store('vehiculos/km-logs', 'public');
            $ticketPath = $request->hasFile('foto_ticket_gasolina')
                ? $data['foto_ticket_gasolina']->store('vehiculos/gasolina-tickets', 'public')
                : null;

            $log = VehiculoEmpleadoKmLog::create([
                'vehiculo_empleado_id' => $asignacion->id,
                'obra_id' => $obraActiva->id,
                'fecha' => now(),
                'km' => (int) $data['km'],
                'foto' => $path,
                'foto_ticket_gasolina' => $ticketPath,
                'monto_gasolina' => $data['monto_gasolina'] ?? null,
                'notas' => $data['notas'] ?? null,
            ]);

            $asignacion->km_final = (int) $data['km'];
            $asignacion->save();

            return response()->json([
                'ok' => true,
                'message' => 'Kilometraje registrado correctamente.',
                'data' => $this->formatearLog($log),
            ], 201);
        });
    }

    private function obraActivaParaUsuario(Request $request): ?Obra
    {
        $user = $request->user()?->loadMissing('usuarioApp');
        $usuarioApp = $user?->usuarioApp;

        if (! $user || ! $usuarioApp) {
            return null;
        }

        $obraId = $request->input('obra_id', $request->query('obra_id'));
        $contexto = $this->contextService->contextoResidente($user, $usuarioApp, $obraId ? (int) $obraId : null);
        $contextoObraId = $contexto['obra']['id'] ?? null;

        if (! $contextoObraId) {
            return null;
        }

        return Obra::query()->find($contextoObraId);
    }

    private function vehiculoActivoParaContexto(Request $request, ?int $empleadoId, ?Obra $obraActiva): ?VehiculoEmpleado
    {
        $user = $request->user()?->loadMissing('usuarioApp');
        $usuarioApp = $user?->usuarioApp;

        if ($user && $usuarioApp && $obraActiva) {
            $asignacionContexto = $this->contextService->vehiculoActivoParaContexto($user, $usuarioApp, $obraActiva);

            if ($asignacionContexto) {
                return $asignacionContexto;
            }
        }

        if (! $empleadoId) {
            return null;
        }

        return VehiculoEmpleado::with(['vehiculo', 'empleado'])
            ->where('empleado_id', $empleadoId)
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_asignacion')
            ->first();
    }

    private function formatearAsignacion(VehiculoEmpleado $asignacion): array
    {
        $vehiculo = $asignacion->vehiculo;
        $empleado = $asignacion->empleado;
        $nombreVehiculo = trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? ''));

        return [
            'id' => $asignacion->id,
            'vehiculo_id' => $asignacion->vehiculo_id,
            'empleado_id' => $asignacion->empleado_id,
            'fecha_asignacion' => optional($asignacion->fecha_asignacion)->format('Y-m-d'),
            'km_inicial' => $asignacion->km_inicial !== null ? (int) $asignacion->km_inicial : null,
            'km_final' => $asignacion->km_final !== null ? (int) $asignacion->km_final : null,
            'vehiculo' => $vehiculo ? [
                'id' => $vehiculo->id,
                'nombre' => $nombreVehiculo !== '' ? $nombreVehiculo : 'Vehiculo',
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'placas' => $vehiculo->placas,
            ] : null,
            'empleado' => $empleado ? [
                'id' => $empleado->id_Empleado,
                'nombre' => trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? '')),
            ] : null,
        ];
    }
    private function formatearLog(VehiculoEmpleadoKmLog $log): array
    {
        return [
            'id' => $log->id,
            'vehiculo_empleado_id' => $log->vehiculo_empleado_id,
            'obra_id' => $log->obra_id,
            'fecha' => $log->fecha->toDateTimeString(),
            'km' => (int) $log->km,
            'foto_url' => $log->foto ? Storage::disk('public')->url($log->foto) : null,
            'foto_ticket_gasolina_url' => $log->foto_ticket_gasolina ? Storage::disk('public')->url($log->foto_ticket_gasolina) : null,
            'monto_gasolina' => $log->monto_gasolina !== null ? (float) $log->monto_gasolina : null,
            'notas' => $log->notas,
        ];
    }
}

