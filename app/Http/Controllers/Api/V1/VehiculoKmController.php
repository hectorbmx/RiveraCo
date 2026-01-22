<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
        return response()->json(['ok'=>false,'message'=>'No hay empleado asociado a este usuario.'], 422);
    }

    $asignacion = VehiculoEmpleado::where('empleado_id', $empleadoId)
        ->whereNull('fecha_fin')
        ->orderByDesc('fecha_asignacion')
        ->first();

    if (!$asignacion) {
        return response()->json(['ok'=>false,'message'=>'No tienes un vehículo asignado actualmente.'], 404);
    }

    $logs = VehiculoEmpleadoKmLog::where('vehiculo_empleado_id', $asignacion->id)
        ->orderByDesc('fecha')
        ->limit(50)
        ->get()
        ->map(fn($l) => [
            'id' => $l->id,
            'fecha' => $l->fecha->toDateTimeString(),
            'km' => (int)$l->km,
            'foto_url' => Storage::disk('public')->url($l->foto),
            'notas' => $l->notas,
        ]);

    return response()->json([
        'ok' => true,
        'vehiculo_empleado_id' => $asignacion->id,
        'data' => $logs
    ]);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'km'   => ['required','integer','min:0'],
            'foto' => ['required','image','mimes:jpg,jpeg,png','max:5120'], // 5MB
            'notas'=> ['nullable','string','max:500'],
        ]);

       $user = $request->user()->load('usuarioApp');

        $empleadoId = $user->usuarioApp?->empleado_id;

        if (!$empleadoId) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay empleado asociado a este usuario.'
            ], 422);
        }


        // Asignación activa del empleado
        $asignacion = VehiculoEmpleado::where('empleado_id', $empleadoId)
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_asignacion')
            ->first();

        if (!$asignacion) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes un vehículo asignado actualmente.'
            ], 404);
        }

        // Validación: no permitir km menor al inicial o al último log
        $ultimoKmLog = VehiculoEmpleadoKmLog::where('vehiculo_empleado_id', $asignacion->id)
            ->max('km');

        $minPermitido = max(
            (int)($asignacion->km_inicial ?? 0),
            (int)($ultimoKmLog ?? 0)
        );

        if ((int)$data['km'] < $minPermitido) {
            return response()->json([
                'ok' => false,
                'message' => "El kilometraje no puede ser menor a {$minPermitido}."
            ], 422);
        }

        return DB::transaction(function () use ($data, $asignacion) {

            // Guardar foto
            $path = $data['foto']->store('vehiculos/km-logs', 'public');

            $log = VehiculoEmpleadoKmLog::create([
                'vehiculo_empleado_id' => $asignacion->id,
                'fecha' => now(),
                'km'    => (int)$data['km'],
                'foto'  => $path,
                'notas' => $data['notas'] ?? null,
            ]);

            // Actualizar “último conocido” en la asignación
            // (aunque siga activa, esto alimenta tu "KM actual")
            $asignacion->km_final = (int)$data['km'];
            $asignacion->save();

            return response()->json([
                'ok' => true,
                'message' => 'Kilometraje registrado correctamente.',
                'data' => [
                    'id' => $log->id,
                    'vehiculo_empleado_id' => $log->vehiculo_empleado_id,
                    'fecha' => $log->fecha->toDateTimeString(),
                    'km' => $log->km,
                    'foto_url' => Storage::disk('public')->url($log->foto),
                ]
            ], 201);
        });
    }
}
