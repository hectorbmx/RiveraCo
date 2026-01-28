<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
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
   
$raw = $data['checked_at'];
$hasTz = (bool) preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $raw);

$checkedAt = $hasTz
    ? Carbon::parse($raw)->utc()  // Si tiene zona, ya viene en UTC
    : Carbon::parse($raw, 'America/Mexico_City')->utc(); // Si NO tiene zona, interpretarlo como México

$checkedDate = $checkedAt->clone()
    ->timezone('America/Mexico_City')
    ->toDateString();

    // $checkedAt = $checkedAtLocal->clone()->utc(); // <-- guardar en UTC
    // $checkedDate = $checkedAtLocal->toDateString(); // <-- IMPORTANTE: el día “local” del empleado
    // $checkedAt   = \Carbon\Carbon::parse($data['checked_at']);
    // $checkedDate = $checkedAt->toDateString();

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
            // 'checked_at' => $asistencia->checked_at?->toIso8601String(),
            'checked_at' => optional($asistencia->checked_at)->timezone('America/Mexico_City')->toIso8601String(),

            'photo_url' => $asistencia->photo_path
                ? \Storage::disk('public')->url($asistencia->photo_path)
                : null,
        ],
    ], 201);
}
//para las checadas de la app en obra por empleado
public function showEmpleado($obraId, $empleadoId)
{
    $raw = ObraAsistencia::query()
        ->with('empleado')
        ->where('obra_id', $obraId)
        ->where('empleado_id', $empleadoId)
        ->orderBy('checked_at')
        ->get();

    $asistencias = $raw
        ->groupBy(fn ($a) => $a->empleado_id . '|' . $a->checked_at->toDateString())
        ->map(function (Collection $items) {
            $entrada = $items->firstWhere('tipo', 'entrada');
            $salida  = $items->firstWhere('tipo', 'salida');

            return [
                'empleado' => $items->first()->empleado,
                'checked_date' => $items->first()->checked_at->toDateString(),
                'entrada' => $entrada ? [
                    'id' => $entrada->id,
                    'hora' => $entrada->checked_at?->timezone('America/Mexico_City')->format('H:i'), // ✅ Convertir a México
                    'photo_url' => $entrada->photo_path ? Storage::disk('public')->url($entrada->photo_path) : null,
                ] : null,
                'salida' => $salida ? [
                    'id' => $salida->id,
                    'hora' => $salida->checked_at?->timezone('America/Mexico_City')->format('H:i'), // ✅ Convertir a México
                    'photo_url' => $salida->photo_path ? Storage::disk('public')->url($salida->photo_path) : null,
                ] : null,
            ];
        })
        ->values();

    return response()->json([
        'obra_id' => (int) $obraId,
        'empleado_id' => (int) $empleadoId,
        'data' => $asistencias,
    ]);
}

//para las checadas de la app en obra general
public function show(Request $request, $obraId)
{
    $raw = ObraAsistencia::query()
        ->with('empleado')
        ->where('obra_id', $obraId)
        ->orderBy('checked_at')
        ->get();

    $asistencias = $raw
        ->groupBy(fn ($a) => $a->empleado_id . '|' . $a->checked_at->toDateString())
        ->map(function (Collection $items) {
            $entrada = $items->firstWhere('tipo', 'entrada');
            $salida  = $items->firstWhere('tipo', 'salida');

            return [
                'empleado' => $items->first()->empleado,
                'checked_date' => $items->first()->checked_at->toDateString(),

                'entrada' => $entrada ? [
                    'id'         => $entrada->id,
                    'hora'       => $entrada->checked_at?->format('H:i'),
                    'photo_path' => $entrada->photo_path,
                    'photo_url'  => $entrada->photo_path
                        ? Storage::disk('public')->url($entrada->photo_path)
                        : null,
                ] : null,

                'salida' => $salida ? [
                    'id'         => $salida->id,
                    'hora'       => $salida->checked_at?->format('H:i'),
                    'photo_path' => $salida->photo_path,
                    'photo_url'  => $salida->photo_path
                        ? Storage::disk('public')->url($salida->photo_path)
                        : null,
                ] : null,
            ];
        })
        ->values();

    return response()->json([
        'obra_id' => (int) $obraId,
        'data'    => $asistencias,
    ]);
}

public function destroy(Request $request, Obra $obra, $asistencia)
{
    $user = $request->user();

    $data = $request->validate([
        'reason' => ['nullable','string','max:255'],
        // Si quieres permitir borrar la foto físicamente al hacer delete:
        'delete_photo' => ['nullable','boolean'],
    ]);

    // 1) Buscar asistencia FORZANDO que sea de esa obra
    $row = \App\Models\ObraAsistencia::query()
        ->where('obra_id', $obra->id)
        ->whereNull('deleted_at')
        ->findOrFail($asistencia);

    // 2) (Opcional) Autorización: aquí puedes meter Gate/Policy/roles
    // Ej: solo el que la registró o un admin/residente
    // if ($row->registrado_por_user_id !== $user->id && !$user->hasRole('admin')) { ... }

    // 3) Soft delete con auditoría
    \DB::transaction(function () use ($row, $user, $data) {

        $row->deleted_by_user_id = $user->id;
        $row->delete_reason = $data['reason'] ?? null;
        $row->save();

        $deletePhoto = (bool)($data['delete_photo'] ?? false);

        // RECOMENDACIÓN: en soft delete normalmente NO borres la foto.
        // Si aun así lo quieres permitir:
        if ($deletePhoto && $row->photo_path) {
            \Storage::disk('public')->delete($row->photo_path);
            $row->photo_path = null;
            $row->save();
        }

        $row->delete(); // soft delete (set deleted_at)
    });

    return response()->json([
        'ok' => true,
        'message' => 'Asistencia eliminada.',
        'data' => [
            'id' => $row->id,
            'obra_id' => $row->obra_id,
            'empleado_id' => $row->empleado_id,
        ],
    ], 200);
}


}
