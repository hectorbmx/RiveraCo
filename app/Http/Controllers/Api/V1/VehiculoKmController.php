<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\VehiculoEmpleado;
use App\Models\VehiculoEmpleadoKmLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehiculoKmController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('usuarioApp');
        $empleadoId = $user->usuarioApp?->empleado_id;

        if (!$empleadoId) {
            return response()->json(['ok' => false, 'message' => 'No hay empleado asociado a este usuario.'], 422);
        }

        $asignacion = VehiculoEmpleado::where('empleado_id', $empleadoId)
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_asignacion')
            ->first();

        if (!$asignacion) {
            return response()->json(['ok' => false, 'message' => 'No tienes un vehiculo asignado actualmente.'], 404);
        }

        $obraActiva = $this->obraActivaDelEmpleado((int) $empleadoId);

        if (!$obraActiva) {
            return response()->json([
                'ok' => true,
                'vehiculo_empleado_id' => $asignacion->id,
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
        ]);

        $user = $request->user()->load('usuarioApp');
        $empleadoId = $user->usuarioApp?->empleado_id;

        if (!$empleadoId) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay empleado asociado a este usuario.',
            ], 422);
        }

        $asignacion = VehiculoEmpleado::where('empleado_id', $empleadoId)
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_asignacion')
            ->first();

        if (!$asignacion) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes un vehiculo asignado actualmente.',
            ], 404);
        }

        $obraActiva = $this->obraActivaDelEmpleado((int) $empleadoId);

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

    private function obraActivaDelEmpleado(int $empleadoId): ?Obra
    {
        $asignacionObra = ObraEmpleado::query()
            ->with('obra')
            ->where('empleado_id', $empleadoId)
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_baja')
                    ->orWhereDate('fecha_baja', '>=', now()->toDateString());
            })
            ->whereHas('obra', function ($query) {
                $query->whereNotIn('estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA]);
            })
            ->orderByDesc('fecha_alta')
            ->orderByDesc('id')
            ->first();

        return $asignacionObra?->obra;
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
