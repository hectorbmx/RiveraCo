<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\ObraAsistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;


class AsistenciasController extends Controller
{
    public function store(Request $request, Obra $obra)
{
    $user = $request->user();

    // 1) Validación base (sin tipo)
    $data = $request->validate([
        'empleado_id'      => ['required','integer'],
        'checked_at'       => ['required','string'],

        // foto se valida después según el tipo determinado
        'foto'             => ['nullable','file'],

        'lat'              => ['nullable','numeric'],
        'lng'              => ['nullable','numeric'],
        'ubicacion_texto'  => ['nullable','string','max:255'],
        'meta'             => ['nullable','array'],
    ]);

    // 2) Parsear fecha/hora del dispositivo
    $checkedAt   = \Carbon\Carbon::parse($data['checked_at']);
    $checkedDate = $checkedAt->toDateString();

    // 3) Verificar que el empleado pertenezca a la obra (ajusta tabla/columnas si aplica)
    $pertenece = \DB::table('obra_empleado')
        ->where('obra_id', $obra->id)
        ->where('empleado_id', $data['empleado_id'])
        ->where('activo', 1)
        ->exists();

    if (!$pertenece) {
        return response()->json([
            'ok' => false,
            'message' => 'El empleado no pertenece a esta obra.'
        ], 403);
    }

    // 4) Determinar tipo automáticamente y aplicar candados en transacción
    $asistencia = \DB::transaction(function () use ($request, $data, $obra, $user, $checkedAt, $checkedDate) {

        // Bloquea las filas del día para evitar carreras (entrada/salida simultánea)
        $existentes = \App\Models\ObraAsistencia::where('obra_id', $obra->id)
            ->where('empleado_id', $data['empleado_id'])
            ->where('checked_date', $checkedDate)
            ->lockForUpdate()
            ->get()
            ->keyBy('tipo');

        $entrada = $existentes->get('entrada');
        $salida  = $existentes->get('salida');

        if (!$entrada && !$salida) {
            $tipo = 'entrada';
        } elseif ($entrada && !$salida) {
            $tipo = 'salida';

            // Candado: salida no menor que entrada
            if ($checkedAt->lt($entrada->checked_at)) {
                abort(response()->json([
                    'ok' => false,
                    'message' => 'La salida no puede ser menor que la entrada.',
                ], 422));
            }
        } else {
            // Ya tiene entrada y salida
            abort(response()->json([
                'ok' => false,
                'message' => 'Este empleado ya tiene entrada y salida registradas hoy.',
            ], 409));
        }

        // Validación condicional de foto (entrada obligatoria)
        if ($tipo === 'entrada') {
            $request->validate([
                'foto' => ['required','image','mimes:jpg,jpeg,png','max:4096'],
            ]);
        } else {
            // salida: si viene foto, validar tipo de archivo
            if ($request->hasFile('foto')) {
                $request->validate([
                    'foto' => ['image','mimes:jpg,jpeg,png','max:4096'],
                ]);
            }
        }

        // Guardar foto si viene
        $photoPath = null;
        if ($request->hasFile('foto')) {
            $photoPath = $request->file('foto')->store('asistencias', 'public');
        }

        return \App\Models\ObraAsistencia::create([
            'obra_id'                => $obra->id,
            'empleado_id'            => $data['empleado_id'],
            'registrado_por_user_id' => $user->id,
            'tipo'                   => $tipo,
            'checked_at'             => $checkedAt,
            'checked_date'           => $checkedDate,
            'photo_path'             => $photoPath,
            'lat'                    => $data['lat'] ?? null,
            'lng'                    => $data['lng'] ?? null,
            'ubicacion_texto'        => $data['ubicacion_texto'] ?? null,
            'meta'                   => $data['meta'] ?? null,
        ]);
    });

    return response()->json([
        'ok' => true,
        'message' => 'Asistencia registrada.',
        'data' => [
            'id' => $asistencia->id,
            'tipo' => $asistencia->tipo,
            'checked_at' => $asistencia->checked_at?->toIso8601String(),
            'photo_url' => $asistencia->photo_path
                ? \Storage::disk('public')->url($asistencia->photo_path)
                : null,
        ],
    ], 201);
}

}
